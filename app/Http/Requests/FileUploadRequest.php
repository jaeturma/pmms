<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class FileUploadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var array<int, string> $extensions */
        $extensions = config('uploads.allowed_extensions');

        return [
            'file' => [
                'required',
                File::types($extensions)->max((int) config('uploads.max_kb')),
            ],
        ];
    }
}
