<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'course_id'         => $this->course_id,
            'created_by'        => $this->created_by,
            'week_number'       => $this->week_number,
            'title'             => $this->title,
            'instructions'      => $this->instructions,
            'due_at'            => $this->due_at?->toIso8601String(),
            'max_score'         => (float) $this->max_score,
            'allow_late'        => (bool) $this->allow_late,
            'status'            => $this->status,
            'course'            => new CourseResource($this->whenLoaded('course')),
            'creator'           => new UserResource($this->whenLoaded('creator')),
            'submissions_count' => $this->whenCounted('submissions'),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
