@component('mail::message')
# Document Rejected – Action Required

Hi {{ $user->name ?? 'Driver' }},

Your **{{ $documentType }}** document has been rejected and needs to be re-uploaded.

@if($reason)
**Reason:** {{ $reason }}
@endif

Please open the ShopittPlus Driver app, go to **Settings → Documents**, and upload a new document. Ensure the photo is clear and all text is readable.

If you have questions, contact our support team.

Thanks,<br>
The ShopittPlus Team
@endcomponent
