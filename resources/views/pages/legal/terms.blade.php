@php
    $toc = [
        ['id' => 'definitions', 'label' => '۱. تعاریف و ماهیت خدمات'],
        ['id' => 'account-security', 'label' => '۲. حساب کاربری و امنیت دسترسی'],
        ['id' => 'acceptable-use', 'label' => '۳. قوانین استفاده مجاز و ممنوعیت‌ها'],
        ['id' => 'third-party-and-ai', 'label' => '۴. سرویس‌های ثالث و هوش مصنوعی'],
        ['id' => 'quotas-and-limits', 'label' => '۵. سهمیه مصرف و پایداری زیرساخت'],
        ['id' => 'billing-and-plans', 'label' => '۶. شرایط مالی، اشتراک و تمدید'],
        ['id' => 'support-rules', 'label' => '۷. قوانین پشتیبانی'],
        ['id' => 'liability', 'label' => '۸. سلب مسئولیت و تغییرات توافق‌نامه'],
    ];
@endphp

<x-legal.layout
    title="قوانین و مقررات استفاده از تمشک فلو"
    subtitle="شرایط، ضوابط و تعهدات متقابل کاربران و پلتفرم اتوماسیون گردش‌کار و هوش مصنوعی تمشک فلو."
    icon="file-text"
    updated-at="آبان ۱۴۰۵"
    active-page="terms"
    :toc="$toc">

    <section id="definitions" class="scroll-mt-24 space-y-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <x-ui.icon name="hierarchy-2" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۱. تعاریف و ماهیت خدمات</h2>
        </div>

        <p class="text-content-muted">
            <strong class="text-content-main">تمشک فلو</strong> یک پلتفرم نرم‌افزاری مبتنی بر فضای ابری برای طراحی، زمان‌بندی و اجرای خودکار گردش‌کارها و ایجنت‌های هوشمند است. ایجاد حساب کاربری یا استفاده از هر یک از خدمات این سامانه به منزله مطالعه، آگاهی و پذیرش کامل مفاد این توافق‌نامه است.
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">نقش زیرساختی پلتفرم:</strong> تمشک فلو صرفاً بستر فنی و ابزارهای نرم‌افزاری را جهت ایجاد و اجرای خودکار فرآیندها در اختیار کاربر قرار می‌دهد و هیچ‌گونه دخالت، مالکیت یا نظارت پیشینی بر منطق گردش‌کارها، دستورات تنظیم‌شده و داده‌های عبوری کاربران ندارد.
            </li>
            <li>
                <strong class="text-content-main">مسئولیت کاربر:</strong> کلیه مسئولیت‌های حقوقی، فنی و قانونی ناشی از طراحی گردش‌کارها، اتصال به سرویس‌های خارجی و محتوای پردازش‌شده یا ارسال‌شده از طریق پلتفرم، کاملاً بر عهده کاربرِ ایجادکننده آن است.
            </li>
        </ul>
    </section>

    <section id="account-security" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                <x-ui.icon name="key" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۲. شرایط حساب کاربری و امنیت دسترسی</h2>
        </div>

        <p class="text-content-muted">
            برای دسترسی به خدمات پلتفرم، ثبت‌نام از طریق آدرس ایمیل معتبر و تایید هویت در سامانه الزامی است.
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li>مسئولیت حفظ محرمانگی رمز عبور، امنیت ایمیل متصل و مدیریت نشست‌های فعال کاملاً بر عهده کاربر است.</li>
            <li>ایجاد حساب‌های کاربری متعدد توسط یک شخص یا مجموعه با هدف دور زدن محدودیت‌های پلن رایگان یا استفاده غیرمجاز از منابع سامانه، تخلف محسوب شده و منجر به مسدودسازی حساب‌ها خواهد شد.</li>
            <li>در صورت مشاهده هرگونه دسترسی غیرمجاز به حساب کاربری یا افشای کلیدهای ارتباطی، کاربر موظف است در اسرع وقت نسبت به تغییر رمز عبور و بروزرسانی اعتبارنامه‌های خود در پنل اقدام نماید.</li>
        </ul>
    </section>

    <section id="acceptable-use" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-500">
                <x-ui.icon name="ban" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۳. قوانین استفاده مجاز و ممنوعیت‌ها</h2>
        </div>

        <p class="text-content-muted">
            کاربران متعهد می‌شوند از زیرساخت اجرایی و امکانات تمشک فلو صرفاً در چارچوب قوانین جمهوری اسلامی ایران و استانداردهای اخلاقی و فنی وب استفاده نمایند. انجام اقدامات زیر در پلتفرم ممنوع است:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">ارسال هرزنامه و مزاحمت:</strong> استفاده از گردش‌کارها برای ارسال پیام‌های تبلیغاتی ناخواسته انبوه، فیشینگ، کلاهبرداری یا فریب اشخاص.
            </li>
            <li>
                <strong class="text-content-main">حملات شبکه‌ای و سوءاستفاده فنی:</strong> استفاده از زیرساخت پلتفرم برای ارسال درخواست‌های مخرب، حملات منع سرویس، اسکن شبکه، نفوذ به سرورها، استخراج غیرمجاز اطلاعات یا تلاش برای دسترسی به شبکه‌های داخلی و غیرمجاز.
            </li>
            <li>
                <strong class="text-content-main">محتوای غیرقانونی:</strong> تولید، پردازش یا انتشار هرگونه محتوای مجرمانه، خلاف قوانین جاری کشور، توهین‌آمیز، ناقض حریم خصوصی یا ناقض حقوق مالکیت فکری اشخاص ثالث.
            </li>
            <li>
                <strong class="text-content-main">ایجاد اختلال در زیرساخت:</strong> طراحی فرآیندهایی با هدف مصرف غیرمتعارف منابع پردازشی سرور یا ایجاد حلقه‌های تکرارشونده مخرب.
            </li>
        </ul>

        <x-docs.callout type="danger" title="برخورد با تخلفات">
            در صورت شناسایی هر یک از تخلفات فوق یا دریافت گزارش تخلف از سوی سرویس‌دهندگان خارجی یا مراجع قانونی، تمشک فلو حق دارد بدون اخطار قبلی، اجرای گردش‌کارهای مربوطه را متوقف و حساب کاربری متخلف را بدون بازگشت وجه مسدود نماید.
        </x-docs.callout>
    </section>

    <section id="third-party-and-ai" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                <x-ui.icon name="brand:ai-agent" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۴. سرویس‌های شخص ثالث و هوش مصنوعی</h2>
        </div>

        <p class="text-content-muted">
            عملکرد بسیاری از بخش‌ها در تمشک فلو مبتنی بر ارتباط با وب‌سرویس‌های خارجی و ارائه‌دهندگان مدل‌های هوش مصنوعی است که با اعتبارنامه‌ها و کلیدهای دسترسی خودِ کاربر متصل می‌شوند:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">هزینه سرویس‌های خارجی:</strong> هزینه اشتراک تمشک فلو صرفاً مربوط به استفاده از زیرساخت پلتفرم است. تهیه کلیدهای دسترسی و پرداخت هزینه‌های مصرف در سرویس‌های شخص ثالث (از جمله سرویس‌های هوش مصنوعی یا پیام‌رسان‌ها) بر عهده شخص کاربر در همان سرویس‌ها است.
            </li>
            <li>
                <strong class="text-content-main">پایداری سرویس‌های خارجی:</strong> تمشک فلو هیچ‌گونه کنترلی بر محدودیت‌های سرویس‌دهندگان خارجی، تغییر ساختار فنی آن‌ها، مسدودشدن کلید دسترسی کاربر، یا اختلالات ارتباطی خارج از شبکه خود ندارد.
            </li>
            <li>
                <strong class="text-content-main">مسئولیت سرورهای مقصد و وب‌سرویس‌ها:</strong> تمشک فلو مسئولیتی در قبال سیاست‌های حفظ حریم خصوصی، اعتبار یا امنیت سرورهای مقصد، آدرس‌های وب‌هوک و وب‌سرویس‌های ثالثی که توسط کاربر در گردش‌کارها فراخوانی می‌شوند، بر عهده ندارد و عواقب ارسال داده به سرورهای ناامن یا مشکوک متوجه شخص کاربر است.
            </li>
            <li>
                <strong class="text-content-main">مسئولیت خروجی هوش مصنوعی:</strong> با توجه به ماهیت احتمالی مدل‌های هوش مصنوعی و امکان تولید پاسخ‌های نادقیق، مسئولیت بررسی صحت خروجی‌ها و نظارت بر اقداماتی که ایجنت‌های هوشمند به صورت خودکار انجام می‌دهند، کاملاً بر عهده کاربر است.
            </li>
        </ul>
    </section>

    <section id="quotas-and-limits" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                <x-ui.icon name="bolt" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۵. سهمیه مصرف و پایداری زیرساخت</h2>
        </div>

        <p class="text-content-muted">
            جهت تضمین کیفیت، سرعت و پایداری سرویس برای تمامی کاربران، استفاده از منابع پلتفرم تابع ظرفیت‌های پلن انتخابی و استانداردهای فنی سامانه است:
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">محاسبه سهمیه اجرا:</strong> هر بار اجرای گردش‌کار (چه به صورت خودکار در محیط عملیاتی و چه به صورت تستی در ویرایشگر) از سهمیه اجرای ماهانه حساب کاربری کسر می‌شود.
            </li>
            <li>
                <strong class="text-content-main">سقف گردش‌کارها:</strong> تعداد گردش‌کارهای فعال همزمان و کل گردش‌کارهای قابل ایجاد، متناسب با پلن اشتراک فعال کاربر تعیین می‌گردد.
            </li>
            <li>
                <strong class="text-content-main">محدودیت‌های ایمنی اجرا:</strong> به منظور جلوگیری از ایجاد حلقه‌های بی‌نهایت و مصرف بیش از حد منابع، سامانه دارای محدودیت‌های استاندارد در تعداد مراحل اجرا، دفعات تکرار و مهلت زمانی پاسخگویی در هر نوبت اجرا است.
            </li>
        </ul>
    </section>

    <section id="billing-and-plans" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                <x-ui.icon name="credit-card" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۶. شرایط مالی، اشتراک‌ها و تمدید</h2>
        </div>

        <p class="text-content-muted">
            پرداخت هزینه اشتراک‌ها به صورت آنلاین از طریق درگاه پرداخت اینترنتی انجام شده و بلافاصله پس از تایید تراکنش، ظرفیت‌های پلن انتخابی روی حساب کاربری فعال می‌گردد:
        </p>

        <ul class="list-disc space-y-2.5 pr-5 text-content-muted">
            <li>
                <strong class="text-content-main">محاسبه روزشمار هنگام ارتقا:</strong> در صورتی که کاربر دارای اشتراک فعال باشد و اقدام به ارتقا به پلن بالاتر یا تغییر دوره از ماهانه به سالانه نماید، ارزش روزهای استفاده‌نشده از اشتراک فعلی به‌صورت خودکار محاسبه و از مبلغ قابل پرداخت کسر می‌شود.
            </li>
            <li>
                <strong class="text-content-main">تمدید و تغییر به پلن پایین‌تر:</strong> امکان تمدید دوره فعلی یا تغییر به پلن پایین‌تر در بازه زمانی پایانی اعتبار اشتراک فراهم است. در صورت تغییر به پلن پایین‌تر، گردش‌کارهای فعال مازاد بر ظرفیت پلن جدید به صورت خودکار به حالت متوقف درمی‌آیند.
            </li>
            <li>
                <strong class="text-content-main">مهلت تنفس و انقضای اشتراک:</strong> پس از پایان اعتبار اشتراک، مهلت تنفس مشخصی جهت تمدید بدون وقفه در نظر گرفته می‌شود. در صورت عدم تمدید پس از پایان این مهلت، حساب به پلن پایه بازگشته و گردش‌کارهای فعال به صورت خودکار متوقف می‌شوند.
            </li>
            <li>
                <strong class="text-content-main">سیاست بازگشت وجه:</strong> با توجه به ماهیت دیجیتال خدمات ابری و اختصاص آنی منابع پس از پرداخت، مبالغ پرداخت‌شده بابت خرید یا تمدید اشتراک غیرقابل استرداد است؛ مگر در شرایطی که به دلیل بروز نقص فنی اثبات‌شده در پلتفرم تمشک فلو، امکان استفاده از سرویس فراهم نباشد.
            </li>
        </ul>
    </section>

    <section id="support-rules" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-teal-500/10 text-teal-500">
                <x-ui.icon name="headset" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۷. قوانین پشتیبانی</h2>
        </div>

        <p class="text-content-muted">
            خدمات پشتیبانی از طریق سیستم تیکتینگ داخلی در پنل کاربری ارائه می‌شود و کاربران دارای پلن‌های اشتراکی از اولویت بالاتری در بررسی درخواست‌ها برخوردارند.
        </p>

        <ul class="list-disc space-y-2 pr-5 text-content-muted">
            <li>دامنه خدمات پشتیبانی شامل رفع مشکلات فنی پلتفرم، راهنمایی درباره کارکرد بخش‌های مختلف سامانه و پیگیری امور مالی و اشتراک است.</li>
            <li>رعایت ادب و شئونات اخلاقی در مکاتبات پشتیبانی الزامی است و جهت حفظ کیفیت پاسخگویی، ارسال تیکت‌ها و پیام‌های مکرر تابع محدودیت‌های استاندارد سامانه است.</li>
        </ul>
    </section>

    <section id="liability" class="scroll-mt-24 space-y-3 border-t border-line-border/60 pt-6">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-500">
                <x-ui.icon name="alert-triangle" class="size-4.5"/>
            </span>
            <h2 class="text-lg font-extrabold text-content-main">۸. سلب مسئولیت و تغییرات توافق‌نامه</h2>
        </div>

        <p class="text-content-muted">
            تمشک فلو تمام تلاش خود را برای ارائه سرویسی پایدار، امن و بدون وقفه به کار می‌گیرد؛ با این حال، پلتفرم در قبال خسارات غیرمستقیم، عدم النفع، یا تغییرات و حذف ناخواسته داده‌ها در سرویس‌های مقصد که ناشی از تنظیمات و منطق طراحی‌شده توسط خودِ کاربر باشد، مسئولیتی نخواهد داشت.
        </p>

        <p class="text-content-muted">
            تمشک فلو ممکن است در راستای توسعه خدمات یا انطباق با الزامات قانونی، مفاد این توافق‌نامه را بروزرسانی نماید. نسخه جدید بلافاصله پس از انتشار در همین صفحه معتبر بوده و ادامه استفاده از خدمات به منزله پذیرش آن است.
        </p>
    </section>

</x-legal.layout>
