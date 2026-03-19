<?php
// مثال على كيفية استخدام SignatureColumn في أي DataTable

// في ملف Table (مثل OfferTable.php)
use Alphasky\Table\Columns\SignatureColumn;

class OfferTable extends TableAbstract
{
    public function columns(): array
    {
        return [
            // الأعمدة الأخرى...
            
            // استخدام عمود التوقيع الأساسي
            SignatureColumn::make('signature')
                ->title(trans('plugins/offer::offer.signature')),
                
            // استخدام عمود التوقيع مع تخصيص الحجم
            SignatureColumn::make('customer_signature')
                ->title('توقيع العميل')
                ->signatureWidth(100)
                ->signatureHeight(50),
                
            // استخدام عمود التوقيع مع تخصيصات متقدمة
            SignatureColumn::make('manager_signature')
                ->title('توقيع المدير')
                ->signatureSize(120, 60)  // العرض والارتفاع معاً
                ->showBorder(true)
                ->showPlaceholder(true)
                ->placeholderText('غير موقع'),
                
            // عمود التوقيع بدون border وبدون placeholder
            SignatureColumn::make('witness_signature')
                ->title('توقيع الشاهد')
                ->signatureWidth(90)
                ->signatureHeight(45)
                ->showBorder(false)
                ->showPlaceholder(false),
        ];
    }
}

// في Survey System، سيتم إنشاء العمود تلقائياً عند استخدام type='sign'

// مثال في Excel للاستطلاعات:
/*
| type | name      | label     | tableshow | signature_width | signature_height |
|------|-----------|-----------|-----------|-----------------|------------------|
| sign | signature | التوقيع   | 1         | 100             | 50              |
*/

// ملاحظات مهمة:
// 1. العمود يدعم base64 data URLs
// 2. العمود يدعم مسارات الملفات العادية
// 3. النقر على التوقيع يفتح modal للعرض والتحميل
// 4. العمود متجاوب مع الشاشات الصغيرة
// 5. يعرض placeholder عند عدم وجود توقيع

?>