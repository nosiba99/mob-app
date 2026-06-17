{{-- resources/views/emails/otp.blade.php --}}
<x-mail::message>
# مرحباً {{ $user->name }} 👋

رمز التحقق الخاص بك هو:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

⏳ هذا الرمز صالح لمدة **10 دقائق** فقط.

إذا لم تطلب هذا الرمز، تجاهل هذا الإيميل.

{{ config('app.name') }}
</x-mail::message>