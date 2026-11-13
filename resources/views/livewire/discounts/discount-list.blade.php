<?php

use App\Models\DiscountCode;
use App\Models\Payment;
use App\Workflow\Support\DateTimeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $code = '';
    public string $title = '';
    public string $type = 'percent';
    public string $value = '';
    public string $maxDiscountAmount = '';
    public string $minOrderAmount = '';
    public string $planRestriction = 'all';
    public string $cycleRestriction = 'all';
    public string $maxUses = '';
    public string $maxUsesPerUser = '1';
    public string $expiryMode = 'unlimited';
    public string $validDays = '30';
    public bool $firstPurchaseOnly = false;
    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('viewAny', DiscountCode::class);
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->status = in_array($this->status, ['all', 'active', 'inactive'], true) ? $this->status : 'all';
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr($this->search, 0, 100);
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, ['all', 'active', 'inactive'], true) || $this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function generateRandomCode(): void
    {
        do {
            $candidate = 'TMK-' . strtoupper(Str::random(6));
        } while (DiscountCode::query()->where('code', $candidate)->exists());

        $this->code = $candidate;
    }

    public function openCreate(): void
    {
        $this->authorize('create', DiscountCode::class);

        $this->editingId = null;
        $this->code = '';
        $this->title = '';
        $this->type = 'percent';
        $this->value = '';
        $this->maxDiscountAmount = '';
        $this->minOrderAmount = '';
        $this->planRestriction = 'all';
        $this->cycleRestriction = 'all';
        $this->maxUses = '';
        $this->maxUsesPerUser = '1';
        $this->expiryMode = 'unlimited';
        $this->validDays = '30';
        $this->firstPurchaseOnly = false;
        $this->isActive = true;

        $this->resetValidation();
        $this->dispatch('open-modal', name: 'discount-form-modal');
    }

    public function openEdit(int $id): void
    {
        $discount = DiscountCode::query()->findOrFail($id);
        $this->authorize('update', $discount);

        $this->editingId = $discount->id;
        $this->code = $discount->code;
        $this->title = $discount->title;
        $this->type = $discount->type;
        $this->value = (string) $discount->value;
        $this->maxDiscountAmount = $discount->max_discount_amount !== null ? (string) $discount->max_discount_amount : '';
        $this->minOrderAmount = $discount->min_order_amount !== null ? (string) $discount->min_order_amount : '';
        $this->planRestriction = $discount->applicable_plan_slugs[0] ?? 'all';
        $this->cycleRestriction = $discount->applicable_cycles[0] ?? 'all';
        $this->maxUses = $discount->max_uses !== null ? (string) $discount->max_uses : '';
        $this->maxUsesPerUser = $discount->max_uses_per_user !== null ? (string) $discount->max_uses_per_user : '';
        $this->expiryMode = $discount->expires_at !== null ? 'keep' : 'unlimited';
        $this->validDays = '30';
        $this->firstPurchaseOnly = $discount->first_purchase_only;
        $this->isActive = $discount->is_active;

        $this->resetValidation();
        $this->dispatch('open-modal', name: 'discount-form-modal');
    }

    public function saveDiscount(): void
    {
        $this->authorize('create', DiscountCode::class);

        $this->code = strtoupper($this->normalizeDigits($this->code));
        $this->title = trim(strip_tags($this->title));
        $this->value = $this->normalizeNumberString($this->value);
        $this->maxDiscountAmount = $this->normalizeNumberString($this->maxDiscountAmount);
        $this->minOrderAmount = $this->normalizeNumberString($this->minOrderAmount);
        $this->maxUses = $this->normalizeNumberString($this->maxUses);
        $this->maxUsesPerUser = $this->normalizeNumberString($this->maxUsesPerUser);
        $this->validDays = $this->normalizeNumberString($this->validDays);

        $validated = $this->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'min:3',
                    'max:40',
                    'regex:/^[A-Z0-9_-]+$/',
                    Rule::unique('discount_codes', 'code')->ignore($this->editingId),
                ],
                'title' => ['required', 'string', 'min:2', 'max:150'],
                'type' => ['required', 'string', Rule::in(['percent', 'fixed'])],
                'value' => [
                    'required',
                    'integer',
                    'min:1',
                    $this->type === 'percent' ? 'max:100' : 'max:100000000',
                ],
                'maxDiscountAmount' => ['nullable', 'integer', 'min:1000', 'max:100000000'],
                'minOrderAmount' => ['nullable', 'integer', 'min:1000', 'max:100000000'],
                'planRestriction' => ['required', 'string', Rule::in(['all', 'pro', 'business'])],
                'cycleRestriction' => ['required', 'string', Rule::in(['all', 'monthly', 'yearly'])],
                'maxUses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
                'maxUsesPerUser' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'expiryMode' => ['required', 'string', Rule::in(['unlimited', 'keep', 'days'])],
                'validDays' => ['required_if:expiryMode,days', 'nullable', 'integer', 'min:1', 'max:3650'],
            ],
            [
                'code.required' => 'وارد کردن کد تخفیف الزامی است.',
                'code.min' => 'کد تخفیف باید حداقل ۳ کاراکتر باشد.',
                'code.regex' => 'کد تخفیف فقط می‌تواند شامل حروف انگلیسی، اعداد، خط تیره (-) و زیرخط (_) باشد.',
                'code.unique' => 'این کد تخفیف قبلاً ثبت شده است.',
                'title.required' => 'عنوان کد تخفیف الزامی است.',
                'value.required' => 'مقدار تخفیف الزامی است.',
                'value.integer' => 'مقدار تخفیف باید یک عدد صحیح باشد.',
                'value.min' => 'مقدار تخفیف باید حداقل ۱ باشد.',
                'value.max' => $this->type === 'percent'
                    ? 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.'
                    : 'مبلغ تخفیف واردشده بیش از حد مجاز است.',
                'maxDiscountAmount.integer' => 'سقف تخفیف باید عدد صحیح باشد.',
                'maxDiscountAmount.min' => 'سقف تخفیف باید حداقل ۱٬۰۰۰ تومان باشد.',
                'minOrderAmount.integer' => 'حداقل مبلغ سفارش باید عدد صحیح باشد.',
                'minOrderAmount.min' => 'حداقل مبلغ سفارش باید حداقل ۱٬۰۰۰ تومان باشد.',
                'maxUses.integer' => 'سقف کل استفاده باید عدد صحیح باشد.',
                'maxUsesPerUser.integer' => 'سقف استفاده هر کاربر باید عدد صحیح باشد.',
                'validDays.required_if' => 'تعداد روز اعتبار را وارد کنید.',
                'validDays.integer' => 'تعداد روز اعتبار باید عدد صحیح باشد.',
                'validDays.min' => 'تعداد روز اعتبار باید حداقل ۱ روز باشد.',
            ],
        );

        $existing = $this->editingId ? DiscountCode::query()->findOrFail($this->editingId) : null;

        if ($existing) {
            $this->authorize('update', $existing);
        }

        $expiresAt = match ($validated['expiryMode']) {
            'days' => now()->addDays((int) $validated['validDays']),
            'keep' => $existing?->expires_at,
            default => null,
        };

        $payload = [
            'code' => $validated['code'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'value' => (int) $validated['value'],
            'max_discount_amount' => ($validated['type'] === 'percent' && $this->maxDiscountAmount !== '')
                ? (int) $this->maxDiscountAmount
                : null,
            'min_order_amount' => $this->minOrderAmount !== '' ? (int) $this->minOrderAmount : null,
            'applicable_plan_slugs' => $validated['planRestriction'] === 'all'
                ? null
                : [$validated['planRestriction']],
            'applicable_cycles' => $validated['cycleRestriction'] === 'all'
                ? null
                : [$validated['cycleRestriction']],
            'max_uses' => $this->maxUses !== '' ? (int) $this->maxUses : null,
            'max_uses_per_user' => $this->maxUsesPerUser !== '' ? (int) $this->maxUsesPerUser : null,
            'first_purchase_only' => $this->firstPurchaseOnly,
            'is_active' => $this->isActive,
            'expires_at' => $expiresAt,
        ];

        if ($existing) {
            $existing->update($payload);
            $this->notify('کد تخفیف با موفقیت بروزرسانی شد.');
        } else {
            DiscountCode::query()->create($payload);
            $this->notify('کد تخفیف جدید با موفقیت ساخته شد.');
        }

        $this->editingId = null;
        unset($this->discounts, $this->stats);
        $this->dispatch('close-modal', name: 'discount-form-modal');
    }

    public function toggleActive(int $id): void
    {
        $discount = DiscountCode::query()->findOrFail($id);
        $this->authorize('update', $discount);

        $discount->update(['is_active' => ! $discount->is_active]);

        unset($this->discounts, $this->stats);
        $this->notify($discount->is_active ? 'کد تخفیف فعال شد.' : 'کد تخفیف غیرفعال شد.');
    }

    public function confirmDelete(int $id): void
    {
        $discount = DiscountCode::query()->findOrFail($id);
        $this->authorize('delete', $discount);

        $this->deletingId = $discount->id;
        $this->dispatch('open-modal', name: 'delete-discount-modal');
    }

    public function deleteDiscount(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $discount = DiscountCode::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $discount);

        $discount->delete();

        $this->deletingId = null;
        unset($this->discounts, $this->stats);
        $this->dispatch('close-modal', name: 'delete-discount-modal');

        if ($this->getPage() > 1 && $this->discounts->isEmpty()) {
            $this->previousPage();
            unset($this->discounts);
        }

        $this->notify('کد تخفیف حذف شد.');
    }

    #[Computed]
    public function stats(): array
    {
        $all = DiscountCode::query()->get();

        return [
            'total' => $all->count(),
            'active' => $all->filter(fn (DiscountCode $d) => $d->isCurrentlyUsable())->count(),
            'total_uses' => (int) $all->sum('used_count'),
            'total_discounted_amount' => (int) Payment::query()
                ->where('status', 'paid')
                ->where('discount_amount', '>', 0)
                ->sum('discount_amount'),
        ];
    }

    #[Computed]
    public function discounts()
    {
        return DiscountCode::query()
            ->search($this->search)
            ->when($this->status === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function currentEditingExpiresPersian(): ?string
    {
        if (! $this->editingId) {
            return null;
        }

        $discount = DiscountCode::query()->find($this->editingId);

        if (! $discount?->expires_at) {
            return null;
        }

        return DateTimeHelper::toPersianDate(
            $discount->expires_at->copy()->setTimezone('Asia/Tehran'),
            'yyyy/MM/dd HH:mm'
        );
    }

    #[Computed]
    public function deleting(): ?DiscountCode
    {
        return $this->deletingId ? DiscountCode::query()->find($this->deletingId) : null;
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'all';
    }

    private function normalizeDigits(string $value): string
    {
        return strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function normalizeNumberString(string $value): string
    {
        $normalized = $this->normalizeDigits($value);

        return str_replace([',', '٬', ' '], '', $normalized);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @php
        $statData = $this->stats;
    @endphp

    <x-discounts.stats
        :total="$statData['total']"
        :active="$statData['active']"
        :total-uses="$statData['total_uses']"
        :total-discounted-amount="$statData['total_discounted_amount']"
    />

    <div class="flex flex-col gap-3 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-ui.icon name="search"
                           class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
                <input type="text"
                       maxlength="100"
                       wire:model.live.debounce.300ms="search"
                       placeholder="جستجو در کد تخفیف یا عنوان..."
                       class="h-9.5 w-full rounded-xl border border-line-border bg-surface-base pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
                @if ($search !== '')
                    <button type="button"
                            wire:click="$set('search', '')"
                            aria-label="پاک کردن جستجو"
                            class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                        <x-ui.icon name="x" class="size-3.5"/>
                    </button>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-1 rounded-xl border border-line-border bg-surface-base p-1">
                @foreach (['all' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال'] as $key => $label)
                    <button type="button"
                            @if ($status !== $key) wire:click="setStatus('{{ $key }}')" @endif
                        @class([
                            'flex-1 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition sm:flex-none',
                            'cursor-default bg-primary text-white font-bold shadow-2xs' => $status === $key,
                            'cursor-pointer font-medium text-content-muted hover:text-content-main' => $status !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <x-ui.button type="button" wire:click="openCreate" class="h-9.5 shrink-0 cursor-pointer">
            <x-ui.icon name="plus" class="size-4"/>
            <span>ایجاد کد تخفیف</span>
        </x-ui.button>
    </div>

    <div class="space-y-2.5">
        @forelse ($this->discounts as $discount)
            <x-discounts.row
                :discount="$discount"
                wire:key="discount-{{ $discount->id }}"
            />
        @empty
            <x-ui.card :padding="false">
                @if ($this->hasFilters)
                    <x-ui.empty-state icon="search"
                                      title="کد تخفیفی یافت نشد"
                                      description="هیچ کد تخفیفی با این عبارت جستجو یا فیلتر پیدا نشد.">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters" class="cursor-pointer">
                            پاک کردن فیلترها
                        </x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="discount-2"
                                      title="هنوز کد تخفیفی ایجاد نکرده‌اید"
                                      description="اولین کد تخفیف خود را با تنظیمات دلخواه (درصدی، مبلغ ثابت، محدود به پلن یا دوره) بسازید.">
                        <x-ui.button type="button" wire:click="openCreate" class="cursor-pointer">
                            <x-ui.icon name="plus" class="size-4"/>
                            <span>ساخت اولین کد تخفیف</span>
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$this->discounts" />

    <x-discounts.modals
        :editing-id="$editingId"
        :type="$type"
        :plan-restriction="$planRestriction"
        :cycle-restriction="$cycleRestriction"
        :expiry-mode="$expiryMode"
        :first-purchase-only="$firstPurchaseOnly"
        :is-active="$isActive"
        :current-expires-persian="$this->currentEditingExpiresPersian"
        :deleting-code="$this->deleting?->code"
    />
</div>
