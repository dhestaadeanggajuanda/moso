<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Enums\PasswordSetup;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Email disimpan dalam huruf kecil, sama seperti Fortify saat login
     * (lowercase_usernames) dan SsoAccountLinker saat mencocokkan email.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'role' => ['required', self::roleRule($this->user())],
            'password_setup' => ['required', Rule::enum(PasswordSetup::class)->only(PasswordSetup::available())],
            'password' => ['exclude_unless:password_setup,'.PasswordSetup::Manual->value, 'required', 'string', Password::default(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['role' => 'peran', 'password_setup' => 'akses awal'];
    }

    /**
     * Peran Super Admin hanya bisa diberikan oleh Super Admin (UserPolicy::assignSuperAdmin).
     */
    public static function roleRule(?User $actor): Enum
    {
        return Rule::enum(RoleName::class)->when(
            ! ($actor?->can('assignSuperAdmin', User::class) ?? false),
            fn (Enum $rule) => $rule->except([RoleName::SuperAdmin]),
        );
    }
}
