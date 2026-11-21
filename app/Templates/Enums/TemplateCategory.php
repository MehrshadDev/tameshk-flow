<?php

namespace App\Templates\Enums;

enum TemplateCategory: string
{
    case SalesOrders = 'sales-orders';
    case CustomerSupport = 'customer-support';
    case AiAssistants = 'ai-assistants';
    case ContentMarketing = 'content-marketing';
    case Operations = 'operations';

    public function label(): string
    {
        return match ($this) {
            self::SalesOrders => 'فروش و سفارش',
            self::CustomerSupport => 'پشتیبانی مشتری',
            self::AiAssistants => 'هوش مصنوعی و ایجنت',
            self::ContentMarketing => 'محتوا و بازاریابی',
            self::Operations => 'عملیات و تیم',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SalesOrders => 'receipt',
            self::CustomerSupport => 'headset',
            self::AiAssistants => 'brand:ai-agent',
            self::ContentMarketing => 'sparkles',
            self::Operations => 'hierarchy',
        };
    }
}
