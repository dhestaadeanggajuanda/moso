<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->account()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->account()->id),
            'role' => ['required', StoreUserRequest::roleRule($this->user())],
        ];
    }

    /**
     * Pengguna tidak boleh mengubah perannya sendiri, sehingga Super Admin tidak
     * bisa mencabut peran Super Admin dari akunnya sendiri. Super Admin melewati
     * Policy, jadi aturan ini dicek di sini.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $account = $this->account();

                if (
                    ! $validator->errors()->has('role')
                    && $account->is($this->user())
                    && ! $account->hasExactRoles([$this->string('role')->toString()])
                ) {
                    $validator->errors()->add('role', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['role' => 'peran'];
    }

    private function account(): User
    {
        $account = $this->route('user');

        abort_unless($account instanceof User, 404);

        return $account;
    }
}
