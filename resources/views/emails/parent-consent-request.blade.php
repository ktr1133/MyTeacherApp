@component('mail::message')
# お子様のアカウント作成に保護者の同意が必要です

保護者の皆様

お子様（{{ $childUser->username }}）がMyTeacherにアカウント登録を試みました。
13歳未満のお子様がMyTeacherをご利用になるには、保護者の方の同意が必要です。

## 登録情報

- **ユーザー名**: {{ $childUser->username }}
- **メールアドレス**: {{ $childUser->email }}
@if($childUser->birthdate)
- **生年月日**: {{ $childUser->birthdate->format('Y年m月d日') }}（{{ $childUser->birthdate->age }}歳）
@endif
- **登録日時**: {{ $childUser->created_at->format('Y年m月d日 H:i') }}

## 同意のお願い

お子様の情報をご確認の上、アカウント作成に同意いただける場合は、以下のボタンをクリックして同意手続きを完了してください。

@component('mail::button', ['url' => $consentUrl, 'color' => 'primary'])
保護者として同意する
@endcomponent

## 重要なお知らせ

@component('mail::panel')
**同意期限**: {{ $childUser->parent_consent_expires_at->format('Y年m月d日 H:i') }}まで

期限までに同意いただけない場合、お子様のアカウントは自動的に削除されます。
削除されたアカウントは復元できませんのでご注意ください。
@endcomponent

## MyTeacherについて

MyTeacherは、AIを活用した学習支援プラットフォームです。お子様の学習習慣の形成をサポートし、
タスク管理やAI教師とのインタラクションを通じて、自主的な学習を促進します。

### 主な機能
- タスク管理・進捗追跡
- AI教師によるサポート
- トークンを使ったAI機能の利用
- 保護者による管理機能

## 法的事項

お子様がMyTeacherを利用するには、以下の内容への同意が必要です：

- [プライバシーポリシー]({{ route('legal.privacy-policy') }})
- [利用規約]({{ route('legal.terms-of-service') }})

同意ボタンをクリックすることで、これらの内容に同意したものとみなされます。

---

## お問い合わせ

ご不明な点がございましたら、以下までお問い合わせください：

- **サポートメール**: support@myteacher.example.com
- **お問い合わせフォーム**: [こちら]({{ url('/contact') }})

---

**注意事項**:
- このメールは {{ $childUser->parent_email }} 宛に送信されました
- 同意リンクは一度のみ有効です
- このメールに心当たりがない場合は、削除していただいて問題ありません

{{ config('app.name') }}
@endcomponent
