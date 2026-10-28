<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

/** Guided planning, without generation or billing. The host persists answers and authenticates the owner. */
final class DesignInterview
{
    public function questions(array $answers, string $locale): array
    {
        $ar = $locale === 'ar';
        $q = [];
        $add = function ($id, $arText, $enText, $options = [], $multiple = false) use (&$q, $ar) {
            $q[] = ['id'=>$id,'text'=>$ar?$arText:$enText,'multiple'=>$multiple,'options'=>array_map(fn($o)=>['value'=>$o[0],'label'=>$ar?$o[1]:$o[2]],$options)];
        };
        $add('idea','احكِ لي عن مشروعك: ما اسمه، ماذا تقدّم، ولمن؟','Tell me about your project: its name, what you offer, and who it is for.');
        $add('goal','ما أهم شيء تريد أن يفعله الزائر في موقعك؟','What is the main thing you want visitors to do?',[
            ['introduce','يتعرّف على عملي ويتواصل معي','Learn about my business and contact me'],['catalogue','يشاهد منتجاتي أو خدماتي','Browse my products or services'],['shop','يشتري ويدفع عبر الموقع','Buy and pay online'],['booking','يحجز موعدًا أو خدمة','Book an appointment or service'],['content','يقرأ مقالاتي وأخباري','Read articles and news'],['other','فكرة أخرى سأشرحها','Another idea I will explain']]);
        if (($answers['goal']??'')==='other') $add('other_goal','ما الذي تحب أن يستطيع الزائر عمله؟ صفه بطريقتك.','What should visitors be able to do? Describe it in your own words.');
        $add('pages','تفضّل صفحة واحدة تجمع كل شيء أم صفحات منفصلة؟','Would you prefer one page containing everything, or separate pages?',[
            ['single','صفحة واحدة','One page'],['multiple','عدة صفحات','Several pages']]);
        $add('sections','ما الأقسام التي تريدها؟ اختر ما يناسبك، ثم اضغط متابعة.','Which sections would you like? Choose what fits, then continue.',[
            ['hero','واجهة ترحيبية / سلايدر','Welcome section / slider'],['about','من نحن','About us'],['services','خدماتنا','Services'],['team','فريق العمل','Our team'],['gallery','معرض الصور والأعمال','Gallery / portfolio'],['blog','مقالات وأخبار','Articles / news'],['contact','تواصل معنا','Contact us']],true);
        $sections=$answers['sections']??[];
        if(in_array('hero',$sections,true))$add('hero','كيف تتخيّل بداية الموقع؟','How do you imagine the top of the website?',[
            ['image','صورة وعنوان ونص وزر','Image, headline, text and button'],['slider','سلايدر بعدة صور ونصوص','Slider with several images and captions']]);
        if(in_array('team',$sections,true))$add('team','ما بيانات الفريق التي تريد إظهارها؟','What team details should appear?',[
            ['photos','صور وأسماء ومناصب','Photos, names and roles'],['bios','صور وأسماء ونبذة لكل شخص','Photos, names and a short biography']]);
        if(in_array('contact',$sections,true)){
            $add('contact','كيف تريد أن يتواصل الزائر معك؟','How should visitors contact you?',[
                ['details','هاتف وبريد فقط','Phone and email only'],['form','هاتف وبريد ونموذج تواصل','Phone, email and contact form']],false);
            $add('map','هل تريد خريطة لموقعك في صفحة التواصل؟','Would you like a location map in the contact section?',[
                ['yes','نعم، أريد خريطة','Yes, include a map'],['no','لا، بدون خريطة','No map']]);
            if(($answers['contact']??'')==='form')$add('fields','ما الحقول المطلوبة في نموذج التواصل؟','Which contact form fields do you need?',[
                ['name','الاسم','Name'],['email','البريد الإلكتروني','Email'],['phone','رقم الهاتف','Phone'],['message','الرسالة','Message']],true);
        }
        if(in_array($answers['goal']??'',['shop','catalogue'],true)){
            $add('products','ماذا تعرض؟ وما أمثلة المنتجات أو الخدمات؟','What do you offer? Give examples of the products or services.');
            $add('product_type','هل هي منتجات تُشحن أم خدمات أم منتجات رقمية؟','Are these physical products, services or digital products?',[
                ['physical','منتجات تُشحن','Physical products'],['services','خدمات','Services'],['digital','منتجات رقمية','Digital products']]);
            if(($answers['goal']??'')==='shop')$add('payments','ما طرق الدفع التي تريدها ضمن الخطة؟','Which payment methods should be included in the plan?',[
                ['paypal','PayPal','PayPal'],['bank','تحويل بنكي','Bank transfer'],['cod','الدفع عند الاستلام','Cash on delivery']],true);
        }
        if(($answers['goal']??'')==='booking'){
            $add('appointments','ما الخدمة التي تُحجز؟ وما مدة الموعد وأوقات العمل؟','What will people book? What is the appointment length and your working schedule?');
            $add('accounts','هل يحتاج الزائر حسابًا لمتابعة حجوزاته؟','Should visitors have an account to manage their bookings?',[
                ['yes','نعم، مع تسجيل دخول','Yes, include sign-in'],['no','الحجز بدون حساب','Book without an account']]);
            $add('reminders','متى تريد تذكير العميل بالموعد؟','When should customers receive an appointment reminder?',[
                ['day','قبل يوم','One day before'],['hour','قبل ساعة','One hour before'],['both','قبل يوم وقبل ساعة','One day and one hour before'],['none','بدون تذكير','No reminder']]);
        }
        $add('logo','هل لديك شعار نستخدمه في التصميم؟','Do you already have a logo for the design?',[
            ['existing','لدي شعار سأضيفه','I have a logo to add'],['create','أحتاج تصميم شعار','I need a logo designed'],['text','أكتفي باسم المشروع الآن','Use the project name for now']]);
        $add('colors','ما الألوان أو الأسلوب الذي تحبه؟ إذا لديك شعار، صف ألوانه، أو اكتب «اقترح لي».','What colors or style do you like? Describe your logo colors, or write “suggest some”.');
        $add('languages','ما لغات الموقع؟','Which languages should the site support?',[
            ['ar','العربية','Arabic'],['en','الإنجليزية','English'],['fr','الفرنسية','French'],['es','الإسبانية','Spanish']],true);
        $add('notes','هل هناك تفاصيل أو وظائف أخرى مهمة؟ اكتبها، أو اختر لا يوجد.','Any other important details or features? Write them, or choose none.',[
            ['none','لا يوجد، اعرض الخطة','None, show the plan']]);
        return $q;
    }

    public function answer(array $answers,string $id,mixed $value,string $locale):array
    {
        $questions=$this->questions($answers,$locale);
        $index=array_search($id,array_column($questions,'id'),true);
        if($index===false)throw new \RuntimeException('invalid_design_question');
        $question=$questions[$index];
        // Answers must follow the conversation or edit an already answered question.
        foreach(array_slice($questions,0,$index) as $prior)if(!array_key_exists($prior['id'],$answers))throw new \RuntimeException('invalid_design_question');
        if($question['multiple']){
            if(!is_array($value)||!count($value)||count($value)>10||count(array_filter($value,'is_string'))!==count($value)||array_diff($value,array_column($question['options'],'value')))throw new \RuntimeException('invalid_design_answer');
            $value=array_values(array_unique($value));
        }else{
            if(!is_string($value)||trim($value)===''||mb_strlen($value)>2500)throw new \RuntimeException('invalid_design_answer');
            $value=trim($value);
            if($question['options']&&!in_array($id,['notes'],true)&&!in_array($value,array_column($question['options'],'value'),true))throw new \RuntimeException('invalid_design_answer');
        }
        $answers[$id]=$value;
        // Recompute branches, preserving applicable answers when an earlier choice changes.
        do{$before=array_keys($answers);$answers=array_intersect_key($answers,array_flip(array_column($this->questions($answers,$locale),'id')));}while(array_keys($answers)!==$before);
        return $answers;
    }

    public function view(array $answers,string $locale):array
    {
        $questions=$this->questions($answers,$locale);$history=[];$next=null;
        foreach($questions as $q){
            if(!array_key_exists($q['id'],$answers)){if(!$next)$next=$q;continue;}
            $value=$answers[$q['id']];$labels=array_column($q['options'],'label','value');
            $label=is_array($value)?implode($locale==='ar'?'، ':', ',array_map(fn($v)=>$labels[$v]??$v,$value)):($labels[$value]??$value);
            $history[]=['id'=>$q['id'],'question'=>$q['text'],'answer'=>$label,'value'=>$value,'options'=>$q['options'],'multiple'=>$q['multiple']];
        }
        return ['history'=>$history,'question'=>$next,'complete'=>$next===null,'answered'=>count($history),'total'=>count($questions),
            'plan'=>['project'=>$answers['idea']??'','items'=>$history,'stages'=>$locale==='ar'?['تجهيز الهوية والألوان','إنشاء الصفحات والأقسام','تجهيز الوظائف المطلوبة','إضافة المحتوى ومراجعة الموقع']:['Prepare branding and colors','Create pages and sections','Set up requested features','Add content and review the website']]];
    }
}
