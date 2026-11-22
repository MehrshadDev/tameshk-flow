<section id="condition-node" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">شرط دو مسیره (If Condition)</h2>
    <p class="text-content-muted">
        نود <strong>If Condition</strong> داده‌های ورودی را بررسی می‌کند و بر اساس برقرار بودن یا نبودن شروط، جریان ورکفلو را به خروجی <strong class="text-emerald-500">بله (true)</strong> یا <strong class="text-primary">خیر (false)</strong> هدایت می‌کند.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">ترکیب شروط:</strong> می‌توانید مشخص کنید همه شروط با هم برقرار باشند (<code>AND</code>) یا برقرار بودن حداقل یکی از آن‌ها کافی باشد (<code>OR</code>).</li>
        <li><strong class="text-content-main">پشتیبانی از ۶ نوع داده:</strong> عملگرهای تخصصی برای متن (<code>String</code>)، عدد (<code>Number</code>)، تاریخ و زمان (<code>Date &amp; Time</code>)، بولین (<code>Boolean</code>)، آرایه (<code>Array</code>) و آبجکت (<code>Object</code>) شامل مقایسه عددی، تاریخ قبل/بعد، طول آرایه، خالی بودن، وجود داشتن و تطابق با <code>Regex</code>.</li>
    </ul>
</section>

<section id="switch-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">مسیریابی چندشاخه (Switch)</h2>
    <p class="text-content-muted">
        زمانی که می‌خواهید ورکفلو را بر اساس قوانین شرطی (مثلاً دستورهای کاربر در تلگرام مثل <code>/start</code>، <code>/help</code> یا وضعیت سفارش) به چند مسیر مختلف تقسیم کنید، از نود <strong>Switch</strong> استفاده نمایید.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">قوانین مسیریابی پویا:</strong> می‌توانید تا ۲۰ قانون مسیریابی اضافه کنید، ترتیب بررسی آن‌ها را با کشیدن و رها کردن جابه‌جا نمایید و برای هر قانون نوع داده و عملگر دلخواه تعیین کنید.</li>
        <li><strong class="text-content-main">تغییر نام خروجی (Rename Output):</strong> با فعال کردن این گزینه روی هر قانون، می‌توانید نام دلخواه (مثلاً «سفارش پرداخت‌شده» یا «پشتیبانی») روی خروجی نود روی بوم نمایش دهید.</li>
        <li><strong class="text-content-main">مسیر پیش‌فرض (Fallback Output):</strong> در گزینه‌های بیشتر می‌توانید مشخص کنید در صورت عدم تطابق با هیچ‌کدام از قوانین، داده به خروجی <strong>پیش‌فرض (Default)</strong> برود یا اجرا در همین نقطه متوقف شود.</li>
    </ul>
</section>

<section id="delay-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">وقفه زمانی (Delay)</h2>
    <p class="text-content-muted">
        نود <strong>Delay</strong> اجرای ورکفلو را به مدت مشخصی متوقف می‌کند و سپس داده ورودی را بدون تغییر به نود بعدی می‌فرستد.
    </p>
</section>

<section id="stop-error-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">توقف و خطا (Stop and Error)</h2>
    <p class="text-content-muted">
        با این نود می‌توانید اجرای یک شاخه را متوقف کنید. این نود دو حالت دارد:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">توقف موفق مسیر (Stop Quietly):</strong> ادامه آن مسیر را بدون ثبت خطا متوقف می‌کند.</li>
        <li><strong class="text-content-main">توقف با خطا (Throw Error):</strong> اجرای کل ورکفلو را متوقف کرده و پیام خطای سفارشی شما را در گزارش اجراها ثبت می‌کند.</li>
    </ul>
</section>
