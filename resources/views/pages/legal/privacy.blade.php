@php
    $toc = [
        ['id' => 'collected-data', 'label' => '۱. اطلاعاتی که جمع‌آوری می‌کنیم'],
        ['id' => 'credentials-encryption', 'label' => '۲. امنیت و رمزنگاری اعتبارنامه‌ها'],
        ['id' => 'execution-and-memory', 'label' => '۳. داده‌های اجرایی و حافظه گردش‌کار'],
        ['id' => 'connected-services', 'label' => '۴. تعامل با سرویس‌های متصل و هوش مصنوعی'],
        ['id' => 'cookies-and-storage', 'label' => '۵. کوکی‌ها و حافظه مرورگر'],
        ['id' => 'user-rights', 'label' => '۶. حقوق کاربر و حذف اطلاعات'],
    ];
@endphp

<x-legal.layout
    title="سیاست حفظ حریم خصوصی و امنیت داده‌ها"
    subtitle="نحوه جمع‌آوری، رمزنگاری، پردازش و محافظت از اطلاعات کاربران و اعتبارنامه‌های متصل در پلتفرم تمشک فلو."
    icon="lock"
    updated-at="آبان ۱۴۰۵"
    active-page="privacy"
    :toc="$toc">

    <section id="collected-data" class="scroll-mt-24 space-y-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <x-ui.icon name="database" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۱. اطلاعاتی که دریافت و جمع‌آوری می‌کنیم</h2>
        </div>

        <p class="text-content-muted">
            در تمشک فلو، ما تنها حداقل اطلاعات لازم برای ایجاد حساب کاربری، اجرای صحیح گردش‌کارها و ارائه خدمات پشتیبانی را جمع‌آوری می‌کنیم:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">اطلاعات هویتی حساب:</strong> شامل نام نمایشی، آدرس ایمیل و تصویر پروفایل کاربر. رمز عبور حساب کاربری به صورت متن خام ذخیره نمی‌شود و صرفاً با الگوریتم‌های هش یک‌طرفه و امن نگهداری می‌گردد.
            </li>
            <li>
                <strong class="text-content-main">اطلاعات فنی و امنیتی نشست:</strong> شامل آدرس IP و مشخصات مرورگر جهت مدیریت نشست‌های کاربری، تامین امنیت حساب و جلوگیری از درخواست‌های مخرب و خودکار.
            </li>
            <li>
                <strong class="text-content-main">اطلاعات تراکنش‌های مالی:</strong> هنگام خرید اشتراک از طریق درگاه پرداخت بانکی، صرفاً مبلغ، زمان پرداخت، کد رهگیری تراکنش و شماره کارت ماسک‌شده جهت صدور صورتحساب ثبت می‌شود. هیچ‌یک از اطلاعات حساس بانکی شما در سرورهای تمشک فلو پردازش یا ذخیره نمی‌شود.
            </li>
            <li>
                <strong class="text-content-main">مکاتبات پشتیبانی:</strong> متن پیام‌ها و تصاویر پیوست‌شده توسط کاربر در بخش تیکت‌های پشتیبانی صرفاً جهت بررسی و رفع مشکل توسط تیم فنی نگهداری می‌شود.
            </li>
        </ul>
    </section>

    <section id="credentials-encryption" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                <x-ui.icon name="lock" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۲. امنیت و رمزنگاری اعتبارنامه‌ها</h2>
        </div>

        <p class="text-content-muted">
            حفاظت از کلیدهای ارتباطی، توکن‌ها و دسترسی‌های متصل‌شده توسط کاربران، مهم‌ترین اصل امنیتی در معماری تمشک فلو است:
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">ذخیره‌سازی رمزنگاری‌شده:</strong> تمامی کلیدهای دسترسی، رمزهای عبور و توکن‌های احراز هویت که در بخش «اعتبارنامه‌ها» ثبت می‌کنید، پیش از ذخیره‌سازی در پایگاه داده با الگوریتم‌های استاندارد رمزنگاری متقارن به صورت کامل رمزنگاری می‌شوند.
            </li>
            <li>
                <strong class="text-content-main">عدم نمایش مجدد اطلاعات محرمانه:</strong> پس از ثبت یک اعتبارنامه، مقادیر محرمانه در هیچ‌یک از صفحات یا پاسخ‌های سامانه به صورت متن خام بازگردانده نمی‌شوند.
            </li>
            <li>
                <strong class="text-content-main">ایزوله‌سازی دسترسی‌ها:</strong> هر اعتبارنامه منحصراً به حساب کاربری ایجادکننده آن تعلق دارد و صرفاً در لحظه اجرای گردش‌کارهای همان کاربر، در حافظه موقت سرور رمزگشایی و مصرف می‌شود.
            </li>
        </ul>

        <x-docs.callout type="success" title="توصیه امنیتی">
            توصیه می‌کنیم برای اتصال به سرویس‌های خارجی، کلیدهای دسترسی اختصاصی ایجاد کنید تا در هر زمان بتوانید مجوزهای آن‌ها را به صورت مستقل مدیریت یا لغو نمایید.
        </x-docs.callout>
    </section>

    <section id="execution-and-memory" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                <x-ui.icon name="history" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۳. داده‌های اجرایی و حافظه گردش‌کارها</h2>
        </div>

        <p class="text-content-muted">
            برای اینکه بتوانید فرآیندهای خودکار خود را پایش و خطایابی کنید، داده‌های اجرایی به شکل زیر مدیریت می‌شوند:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">گزارش تاریخچه اجراها:</strong> هنگام اجرای یک گردش‌کار، داده‌های ورودی، خروجی و خطاهای احتمالی مراحل مختلف ثبت می‌شود تا در بخش «تاریخچه اجراها» برای شخص شما قابل بررسی باشد. شما در هر زمان می‌توانید این گزارش‌ها را به صورت تکی یا کلی پاک‌سازی نمایید.
            </li>
            <li>
                <strong class="text-content-main">حافظه گفتگو و داده‌های موقت:</strong> در صورتی که در گردش‌کار خود از قابلیت‌های حافظه گفتگو یا ذخیره‌سازی داده استفاده کنید، این اطلاعات به صورت کاملاً ایزوله و تفکیک‌شده برای حساب کاربری شما نگهداری شده و پس از دوره زمانی مشخص یا به درخواست شما پاک‌سازی می‌شوند.
            </li>
        </ul>
    </section>

    <section id="connected-services" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                <x-ui.icon name="world" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۴. تعامل با سرویس‌های متصل و هوش مصنوعی</h2>
        </div>

        <p class="text-content-muted">
            حریم خصوصی داده‌های شما در تعامل با سرویس‌های خارجی و حساب‌های متصل‌شده، تحت اصول زیر محافظت می‌شود:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">استفاده صرف در راستای دستور کاربر:</strong> دسترسی به حساب‌ها و سرویس‌های متصل‌شده توسط شما (از جمله حساب‌های ابری، ایمیل، پیام‌رسان‌ها یا وب‌سرویس‌ها) منحصراً برای اجرای دستوراتی است که خودتان در گردش‌کار تعریف کرده‌اید.
            </li>
            <li>
                <strong class="text-content-main">عدم فروش و عدم استفاده تبلیغاتی:</strong> تمشک فلو اطلاعات شخصی، داده‌های گردش‌کارها یا اطلاعات دریافتی از حساب‌های متصل شما را به هیچ شخص یا سازمانی نمی‌فروشد، اجاره نمی‌دهد و برای اهداف تبلیغاتی استفاده نمی‌کند.
            </li>
            <li>
                <strong class="text-content-main">عدم آموزش مدل‌های هوش مصنوعی:</strong> ما از داده‌ها، متون و محتوای گردش‌کارهای شما برای آموزش مدل‌های هوش مصنوعی استفاده نمی‌کنیم. ارسال داده به سرویس‌دهندگان هوش مصنوعی تنها زمانی انجام می‌شود که خودِ شما در گردش‌کارتان از آن‌ها استفاده کرده باشید که در این صورت، پردازش داده در مقصد تابع سیاست حریم خصوصی همان سرویس‌دهنده منتخب شما خواهد بود.
            </li>
            <li>
                <strong class="text-content-main">مسئولیت امنیت سرورها و وب‌سرویس‌های مقصد:</strong> تمشک فلو هیچ‌گونه مسئولیتی در قبال سیاست‌های حفظ حریم خصوصی، نحوه نگهداری اطلاعات یا امنیت سرورهای مقصد، وب‌هوک‌ها و وب‌سرویس‌های ثالثی که توسط کاربر در گردش‌کارها فراخوانی می‌شوند، بر عهده ندارد.
            </li>
        </ul>
    </section>

    <section id="cookies-and-storage" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-teal-500/10 text-teal-500">
                <x-ui.icon name="sparkles" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۵. کوکی‌ها و حافظه محلی مرورگر</h2>
        </div>

        <p class="text-content-muted">
            تمشک فلو از هیچ‌گونه کوکی ردیاب تبلیغاتی استفاده نمی‌کند. کوکی‌ها و حافظه مرورگر صرفاً برای عملکرد صحیح و امنیتی سامانه کاربرد دارند:
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li><strong class="text-content-main">کوکی‌های ضروری نشست:</strong> جهت حفظ وضعیت ورود به حساب کاربری و تامین امنیت فرم‌ها در برابر درخواست‌های جعلی.</li>
            <li><strong class="text-content-main">تنظیمات رابط کاربری:</strong> جهت به خاطر سپردن ترجیحات ظاهری شما در مرورگر، مانند انتخاب پوسته تاریک یا روشن و چیدمان پنل‌های محیط کاربری.</li>
        </ul>
    </section>

    <section id="user-rights" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-500">
                <x-ui.icon name="check" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۶. حقوق کاربر و حذف اطلاعات</h2>
        </div>

        <p class="text-content-muted">
            شما مالک داده‌های خود هستید و در هر لحظه می‌توانید اقدامات زیر را مستقیماً از طریق پنل کاربری انجام دهید:
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li><strong class="text-content-main">حذف آنی اعتبارنامه‌ها:</strong> با حذف هر اعتبارنامه از پنل کاربری، کلیدها و توکن‌های رمزنگاری‌شده آن بلافاصله و برای همیشه از پایگاه داده حذف می‌شوند و دسترسی سامانه به آن سرویس قطع می‌گردد.</li>
            <li><strong class="text-content-main">حذف گردش‌کارها و گزارش‌های اجرا:</strong> شما می‌توانید در هر زمان هر یک از گردش‌کارها، حافظه موقت یا تاریخچه اجراهای ثبت‌شده را به طور کامل حذف کنید.</li>
            <li><strong class="text-content-main">درخواست حذف کامل حساب کاربری:</strong> در صورت تمایل به حذف دائمی حساب کاربری و کلیه اطلاعات وابسته به آن، کافی است درخواست خود را از طریق بخش پشتیبانی ثبت نمایید تا در کوتاه‌ترین زمان ممکن انجام شود.</li>
        </ul>
    </section>

</x-legal.layout>
