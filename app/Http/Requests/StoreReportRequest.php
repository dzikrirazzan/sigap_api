<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Any authenticated user can create a report
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'photo' => 'required|mimes:jpeg,jpg,png,gif,bmp,webp,heic,heif,svg|max:20480', // max 20MB, support iPhone HEIC/HEIF
            'location' => 'required|string|max:255',
            'problem_type' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
        ];
    }
}
