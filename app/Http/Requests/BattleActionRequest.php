<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BattleActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|string|in:move,item,switch',
            'move_index' => 'required_if:action,move|integer',
            'item_id' => 'required_if:action,item|integer',
            'target' => 'required_if:action,switch|integer',
        ];
    }
}