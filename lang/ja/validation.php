<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行はバリデータークラスによって使用されるデフォルトのエラーメッセージです。
    | サイズルールのように、いくつかのルールには複数のバージョンがあります。
    | ここでこれらのメッセージを自由に調整してください。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには、:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには、:date以降の日付を指定してください。',
    'alpha' => ':attributeには、アルファベットのみを指定してください。',
    'alpha_dash' => ':attributeには、英数字、ダッシュ、アンダースコアのみを指定してください。',
    'alpha_num' => ':attributeには、英数字のみを指定してください。',
    'any_of' => ':attributeが無効です。',
    'array' => ':attributeには、配列を指定してください。',
    'ascii' => ':attributeには、半角英数字と記号のみを指定してください。',
    'before' => ':attributeには、:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには、:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは、:min個から:max個までの項目を含む必要があります。',
        'file' => ':attributeは、:minキロバイトから:maxキロバイトの間である必要があります。',
        'numeric' => ':attributeは、:minから:maxの間である必要があります。',
        'string' => ':attributeは、:min文字から:max文字の間である必要があります。',
    ],
    'boolean' => ':attributeは、trueまたはfalseである必要があります。',
    'can' => ':attributeに不正な値が含まれています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'contains' => ':attributeに必須の値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは、:dateと同じ日付である必要があります。',
    'date_format' => ':attributeは、:format形式と一致する必要があります。',
    'decimal' => ':attributeは、:decimal桁の小数である必要があります。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherは異なる必要があります。',
    'digits' => ':attributeは、:digits桁である必要があります。',
    'digits_between' => ':attributeは、:min桁から:max桁の間である必要があります。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeは、次のいずれかで終わってはいけません: :values',
    'doesnt_start_with' => ':attributeは、次のいずれかで始まってはいけません: :values',
    'email' => ':attributeには、有効なメールアドレスを指定してください。',
    'ends_with' => ':attributeは、次のいずれかで終わる必要があります: :values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeは、次の拡張子のいずれかである必要があります: :values',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeには値が必要です。',
    'gt' => [
        'array' => ':attributeは、:value個より多い項目を含む必要があります。',
        'file' => ':attributeは、:valueキロバイトより大きい必要があります。',
        'numeric' => ':attributeは、:valueより大きい必要があります。',
        'string' => ':attributeは、:value文字より多い必要があります。',
    ],
    'gte' => [
        'array' => ':attributeは、:value個以上の項目を含む必要があります。',
        'file' => ':attributeは、:valueキロバイト以上である必要があります。',
        'numeric' => ':attributeは、:value以上である必要があります。',
        'string' => ':attributeは、:value文字以上である必要があります。',
    ],
    'hex_color' => ':attributeは、有効な16進数カラーである必要があります。',
    'image' => ':attributeには、画像を指定してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは、:otherに存在する必要があります。',
    'integer' => ':attributeには、整数を指定してください。',
    'ip' => ':attributeには、有効なIPアドレスを指定してください。',
    'ipv4' => ':attributeには、有効なIPv4アドレスを指定してください。',
    'ipv6' => ':attributeには、有効なIPv6アドレスを指定してください。',
    'json' => ':attributeには、有効なJSON文字列を指定してください。',
    'list' => ':attributeはリストである必要があります。',
    'lowercase' => ':attributeは小文字である必要があります。',
    'lt' => [
        'array' => ':attributeは、:value個未満の項目を含む必要があります。',
        'file' => ':attributeは、:valueキロバイト未満である必要があります。',
        'numeric' => ':attributeは、:value未満である必要があります。',
        'string' => ':attributeは、:value文字未満である必要があります。',
    ],
    'lte' => [
        'array' => ':attributeは、:value個以下の項目を含む必要があります。',
        'file' => ':attributeは、:valueキロバイト以下である必要があります。',
        'numeric' => ':attributeは、:value以下である必要があります。',
        'string' => ':attributeは、:value文字以下である必要があります。',
    ],
    'mac_address' => ':attributeは、有効なMACアドレスである必要があります。',
    'max' => [
        'array' => ':attributeは、:max個を超える項目を含むことはできません。',
        'file' => ':attributeは、:maxキロバイト以下である必要があります。',
        'numeric' => ':attributeは、:max以下である必要があります。',
        'string' => ':attributeは、:max文字以下である必要があります。',
    ],
    'max_digits' => ':attributeは、:max桁以下である必要があります。',
    'mimes' => ':attributeには、次のタイプのファイルを指定してください: :values',
    'mimetypes' => ':attributeには、次のタイプのファイルを指定してください: :values',
    'min' => [
        'array' => ':attributeは、少なくとも:min個の項目を含む必要があります。',
        'file' => ':attributeは、少なくとも:minキロバイトである必要があります。',
        'numeric' => ':attributeは、少なくとも:min以上である必要があります。',
        'string' => ':attributeは、少なくとも:min文字以上である必要があります。',
    ],
    'min_digits' => ':attributeは、少なくとも:min桁である必要があります。',
    'missing' => ':attributeフィールドは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeフィールドは存在してはいけません。',
    'missing_unless' => ':otherが:valueでない場合、:attributeフィールドは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeフィールドは存在してはいけません。',
    'missing_with_all' => ':valuesが全て存在する場合、:attributeフィールドは存在してはいけません。',
    'multiple_of' => ':attributeは、:valueの倍数である必要があります。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeには、数値を指定してください。',
    'password' => [
        'letters' => ':attributeには、少なくとも1つの文字を含める必要があります。',
        'mixed' => ':attributeには、少なくとも1つの大文字と1つの小文字を含める必要があります。',
        'numbers' => ':attributeには、少なくとも1つの数字を含める必要があります。',
        'symbols' => ':attributeには、少なくとも1つの記号を含める必要があります。',
        'uncompromised' => '指定された:attributeはデータ漏洩で発見されました。別の:attributeを選択してください。',
    ],
    'present' => ':attributeフィールドが存在する必要があります。',
    'present_if' => ':otherが:valueの場合、:attributeフィールドが存在する必要があります。',
    'present_unless' => ':otherが:valueでない場合、:attributeフィールドが存在する必要があります。',
    'present_with' => ':valuesが存在する場合、:attributeフィールドが存在する必要があります。',
    'present_with_all' => ':valuesが全て存在する場合、:attributeフィールドが存在する必要があります。',
    'prohibited' => ':attributeフィールドは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeフィールドは禁止されています。',
    'prohibited_unless' => ':otherが:valuesにない場合、:attributeフィールドは禁止されています。',
    'prohibits' => ':attributeフィールドは、:otherの存在を禁止します。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeフィールドには、次のエントリが含まれている必要があります: :values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認された場合、:attributeは必須です。',
    'required_if_declined' => ':otherが拒否された場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesにない場合、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが全て存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがどれも存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'array' => ':attributeは、:size個の項目を含む必要があります。',
        'file' => ':attributeは、:sizeキロバイトである必要があります。',
        'numeric' => ':attributeは、:sizeである必要があります。',
        'string' => ':attributeは、:size文字である必要があります。',
    ],
    'starts_with' => ':attributeは、次のいずれかで始まる必要があります: :values',
    'string' => ':attributeは文字列である必要があります。',
    'timezone' => ':attributeは、有効なタイムゾーンである必要があります。',
    'unique' => ':attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字である必要があります。',
    'url' => ':attributeは、有効なURL形式である必要があります。',
    'ulid' => ':attributeは、有効なULIDである必要があります。',
    'uuid' => ':attributeは、有効なUUIDである必要があります。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語行
    |--------------------------------------------------------------------------
    |
    | ここでは、"attribute.rule"の命名規則を使用して、カスタム検証メッセージを
    | 指定できます。これにより、特定の属性ルールに対して特定のカスタム言語行を
    | 迅速に指定できます。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'カスタムメッセージ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、"email"の代わりに"メールアドレス"のような、より読みやすい
    | 属性プレースホルダーに置き換えるために使用されます。これにより、メッセージが
    | より表現力豊かになります。
    |
    */

    'attributes' => [
        'name' => '名前',
        'username' => 'ユーザー名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'password_confirmation' => 'パスワード（確認）',
        'timezone' => 'タイムゾーン',
        'privacy_policy_consent' => 'プライバシーポリシーへの同意',
        'terms_consent' => '利用規約への同意',
        'birthdate' => '生年月日',
        'parent_email' => '保護者のメールアドレス',
        'title' => 'タイトル',
        'description' => '説明',
        'content' => '内容',
        'subject' => '件名',
        'message' => 'メッセージ',
    ],

];
