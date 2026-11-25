<?php

use App\Models\User;
use App\Support\GmailGuard;
use App\Templates\PendingTemplate;
use App\Traits\HasEmailCodeVerification;
use App\Traits\HasFormSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    use HasFormSecurity, HasEmailCodeVerification;

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->form_load_time = time();
    }

    protected function rules(): array
    {
        if ($this->step === 1) {
            return [
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            ];
        }

        return [
            'verificationCode' => ['required', 'string', 'digits:6'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل وارد شده صحیح نیست.',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.letters' => 'رمز عبور باید شامل حروف باشد.',
            'password.numbers' => 'رمز عبور باید شامل اعداد باشد.',
            'verificationCode.required' => 'وارد کردن کد تایید الزامی است.',
            'verificationCode.digits' => 'کد تایید باید ۶ رقم باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'verificationCode' => 'کد تایید',
        ];
    }

    protected function resendMessage(): string
    {
        return 'کد تایید جدید با موفقیت ارسال شد.';
    }

    protected function extraReset(): void
    {
    }

    public function register(): void
    {
        if ($this->step !== 1) {
            return;
        }

        $this->validate();

        if ($gmailError = GmailGuard::validate($this->email)) {
            $this->addError('email', $gmailError);
            return;
        }

        if (! $this->securityChecks('register', 5)) {
            return;
        }

        $email = GmailGuard::normalize($this->email);

        if (User::query()->where('email', $email)->exists()) {
            $this->hitRateLimiter('register', 300);
            $this->addError('email', 'این حساب جیمیل قبلاً ثبت شده است.');
            return;
        }

        if (! $this->issueCode($email, 'email')) {
            return;
        }

        $this->hitRateLimiter('register', 300);
        $this->startVerification($email);
    }

    public function verifyCode(PendingTemplate $pendingTemplate): void
    {
        if ($this->step !== 2) {
            return;
        }

        $this->resendStatus = null;
        $this->validate();

        $record = $this->checkCode('verify-code', 'کد تایید منقضی شده است. لطفاً دوباره ثبت‌نام کنید.');

        if (! $record) {
            return;
        }

        $email = GmailGuard::normalize($this->email);

        if ($gmailError = GmailGuard::validate($email)) {
            $this->resetFlow();
            $this->addError('email', $gmailError);
            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->finishVerification('verify-code', $record);
            $this->resetFlow();
            $this->addError('email', 'این حساب جیمیل قبلاً ثبت شده است. لطفاً وارد شوید.');
            return;
        }

        $user = User::create([
            'name' => $this->extractNameFromEmail($email),
            'email' => $email,
            'password' => Hash::make($this->password),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->finishVerification('verify-code', $record);
        $this->clearRateLimiter('register');

        Auth::login($user, remember: false);

        session()->regenerateToken();

        if ($workflow = $pendingTemplate->consume($user)) {
            $this->redirect(route('app.workflows.show', $workflow));
            return;
        }

        $this->redirect(route('app.workflows.index'), navigate: true);
    }

    protected function extractNameFromEmail(string $email): string
    {
        $username = trim(explode('@', $email, 2)[0] ?? '');

        return $username !== '' ? mb_substr($username, 0, 50) : 'کاربر';
    }
};
?>

<div>
    @if ($step === 1)
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-content-main">ایجاد حساب کاربری</h1>
            <p class="text-sm text-content-muted mt-1">برای شروع، آدرس جیمیل و رمز عبور خود را وارد کنید</p>
        </div>

        <x-auth.alert />

        <form wire:submit="register" class="space-y-4">
            <x-auth.honeypot/>

            <x-ui.input name="email" type="email" wire:model="email" label="جیمیل (فقط @gmail.com)" placeholder="example@gmail.com" autocomplete="email" autofocus/>

            <x-ui.input name="password" type="password" wire:model="password" label="رمز عبور" placeholder="حداقل ۸ کاراکتر شامل حروف و اعداد" autocomplete="new-password"/>

            <x-ui.input name="password_confirmation" type="password" wire:model="password_confirmation" label="تکرار رمز عبور" placeholder="رمز عبور را دوباره وارد کنید" autocomplete="new-password"/>

            <x-auth.submit target="register" label="ارسال کد تایید" loading="در حال ارسال..."/>
        </form>

        <div class="relative my-6 text-center text-xs text-content-muted">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-line-border"></div>
            </div>
            <span class="relative bg-surface-base px-3">یا ثبت‌نام با</span>
        </div>

        <x-auth.google-button label="ثبت‌نام با حساب گوگل"/>

        <p class="mt-4 text-center text-[11px] leading-5 text-content-muted">
            ثبت‌نام در تمشک به منزله پذیرش
            <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-content-main underline decoration-line-border underline-offset-4 transition hover:text-primary">قوانین و مقررات</a>
            و
            <a href="{{ route('privacy') }}" target="_blank" class="font-semibold text-content-main underline decoration-line-border underline-offset-4 transition hover:text-primary">حریم خصوصی</a>
            است.
        </p>

        <p class="mt-5 text-center text-sm text-content-muted">
            حساب دارید؟
            <a href="{{ route('login') }}" wire:navigate class="text-primary hover:underline font-medium cursor-pointer">وارد شوید</a>
        </p>
    @else
        <x-auth.verify-step
            title="تایید جیمیل 📧"
            submit="تایید و ورود"
            :email="$email"
            :expires-at="$codeExpiresAt"
            :resend-at="$resendAvailableAt"
            :status="$resendStatus"
        />
    @endif
</div>
