<x-mail::message>
# Hello **{{ $userName }}**,

We have received your message and here is our reply:

<strong>Your message:</strong> {{ $originalMessage }}

---

<strong>Our reply:</strong> {{ $reply }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>