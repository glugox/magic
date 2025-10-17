<?php


namespace App\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class ApiResponse implements Arrayable, JsonSerializable
{
    protected $content;
    protected array $meta = [];
    protected ?string $message = null;
    protected bool $success = false;
    protected ?array $errors = null;

    public static function success(
        $content, array
        $meta = [],
        ?string $message = null,
        ?array $errors = null
    ): static
    {
        $instance = new static;
        $instance->content = $content;
        $instance->meta = $meta;
        $instance->message = $message;
        $instance->success = true;
        $instance->errors = $errors;

        return $instance;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content instanceof JsonResource
                ? $this->content->resolve(request())
                : $this->content,
            'meta' => $this->meta,
            'message' => $this->message,
            'success' => $this->success,
            'errors' => $this->errors
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
