<?php
// مثال سريع لاختبار SignatureColumn

// استخدام أساسي
SignatureColumn::make('signature')
    ->title('التوقيع');

// استخدام مع تخصيص الحجم
SignatureColumn::make('signature')
    ->title('التوقيع')
    ->signatureWidth(100)
    ->signatureHeight(50);

// استخدام مع جميع الخيارات
SignatureColumn::make('signature')
    ->title('التوقيع')
    ->signatureSize(120, 60)  // العرض والارتفاع معاً
    ->showBorder(true)
    ->showPlaceholder(true)
    ->placeholderText('لا يوجد توقيع');

// للاستخدام في Survey System - سيتم إنشاؤه تلقائياً:
// type: sign
// name: signature
// label: التوقيع
// tableshow: 1

// ملاحظة: تم إصلاح تعارض الوظائف:
// - width() → signatureWidth()
// - height() → signatureHeight() 
// - size() → signatureSize()

?>