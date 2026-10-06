#!/usr/bin/env bash
set -euo pipefail

API_BASE_URL="${API_BASE_URL:-http://127.0.0.1:8000/api/v1}"
: "${API_EMAIL:?Set API_EMAIL to a non-admin account email}"
: "${API_PASSWORD:?Set API_PASSWORD to that account's password}"
: "${LECTURER_ID:?Set LECTURER_ID to an existing dosen user ID}"
: "${OTHER_USER_ID:?Set OTHER_USER_ID to another user's ID}"

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

assert_status() {
    local expected="$1"
    local actual="$2"
    local label="$3"

    if [[ "$actual" != "$expected" ]]; then
        printf '%s: expected HTTP %s, got HTTP %s\n' "$label" "$expected" "$actual" >&2
        exit 1
    fi

    printf '%s: HTTP %s\n' "$label" "$actual"
}

status="$(curl -sS -o "$tmp_dir/unauthenticated.json" -w '%{http_code}' \
    -H 'Accept: application/json' "$API_BASE_URL/users")"
assert_status 401 "$status" 'users without token'

status="$(curl -sS -o "$tmp_dir/unknown-email.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d '{"email":"unknown@example.test","password":"wrong-password"}' \
    "$API_BASE_URL/auth/login")"
assert_status 422 "$status" 'login with unknown email'

status="$(curl -sS -o "$tmp_dir/wrong-password.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d "{\"email\":\"$API_EMAIL\",\"password\":\"wrong-password\"}" \
    "$API_BASE_URL/auth/login")"
assert_status 422 "$status" 'login with wrong password'

php -r '
$unknown = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$wrong = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
if (($unknown["errors"]["email"] ?? null) !== ($wrong["errors"]["email"] ?? null)) {
    fwrite(STDERR, "Login errors disclose whether an email exists.\n");
    exit(1);
}
' "$tmp_dir/unknown-email.json" "$tmp_dir/wrong-password.json"
printf 'login error is generic for both credential failures\n'

status="$(curl -sS -o "$tmp_dir/login.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d "{\"email\":\"$API_EMAIL\",\"password\":\"$API_PASSWORD\"}" \
    "$API_BASE_URL/auth/login")"
assert_status 200 "$status" 'valid login'

token="$(php -r '$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR); echo $data["token"] ?? "";' "$tmp_dir/login.json")"
if [[ -z "$token" ]]; then
    printf 'Login response did not contain a token.\n' >&2
    exit 1
fi

auth_header="Authorization: Bearer $token"
status="$(curl -sS -o "$tmp_dir/users.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H "$auth_header" "$API_BASE_URL/users")"
assert_status 200 "$status" 'users with token'

if grep -Eq '"(password|remember_token)"' "$tmp_dir/users.json"; then
    printf 'User response contains a sensitive field.\n' >&2
    exit 1
fi
printf 'user collection excludes password and remember_token\n'

status="$(curl -sS -o "$tmp_dir/forbidden.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H "$auth_header" \
    "$API_BASE_URL/users/$OTHER_USER_ID")"
assert_status 403 "$status" 'authenticated user reading another account'

course_code="CURL-$(date +%s)"
status="$(curl -sS -o "$tmp_dir/course.json" -w '%{http_code}' \
    -H 'Accept: application/json' -H 'Content-Type: application/json' -H "$auth_header" \
    -d "{\"code\":\"$course_code\",\"name\":\"Curl API check\",\"sks\":3,\"lecturer_id\":$LECTURER_ID,\"status\":\"active\"}" \
    "$API_BASE_URL/courses")"
assert_status 201 "$status" 'create course'

course_id="$(php -r '$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR); echo $data["data"]["id"] ?? "";' "$tmp_dir/course.json")"
if [[ -z "$course_id" ]]; then
    printf 'Course create response did not contain an ID.\n' >&2
    exit 1
fi

status="$(curl -sS -o "$tmp_dir/delete.json" -w '%{http_code}' -X DELETE \
    -H 'Accept: application/json' -H "$auth_header" "$API_BASE_URL/courses/$course_id")"
assert_status 204 "$status" 'delete course'
[[ ! -s "$tmp_dir/delete.json" ]]