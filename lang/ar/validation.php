<?php

// Arabic messages for the rules SafeBrief uses. Anything missing falls back to lang/en/validation.php.
return [
    'array' => 'يجب أن يكون حقل :attribute قائمة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالحًا.',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'in' => 'القيمة المحددة في :attribute غير صالحة.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.',
    ],
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min حرفًا.',
    ],
    'password' => [
        'letters' => 'يجب أن يحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. اختر :attribute مختلفة.',
    ],
    'required' => 'حقل :attribute مطلوب.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'string' => 'يجب أن يكون :attribute بطول :size حرفًا.',
    ],
    'string' => 'يجب أن يكون :attribute نصًا.',
    'unique' => ':attribute مستخدم من قبل.',

    'attributes' => [
        'company_name' => 'اسم الشركة',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'contact' => 'الهاتف أو البريد الإلكتروني',
        'incident_text' => 'تقرير الحادث',
        'title' => 'العنوان',
        'summary' => 'ماذا حدث',
        'key_actions' => 'الإجراءات',
        'key_actions.*' => 'الإجراء',
        'questions' => 'الأسئلة',
        'questions.*.prompt' => 'نص السؤال',
        'questions.*.options' => 'الخيارات',
        'questions.*.options.*' => 'الخيار',
        'questions.*.correct_index' => 'الإجابة الصحيحة',
        'crew_member_ids' => 'أفراد الطاقم',
        'answers' => 'الإجابات',
        'answers.*' => 'الإجابة',
    ],
];
