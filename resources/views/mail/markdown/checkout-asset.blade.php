@component('mail::message')
# {{ trans('mail.hello').' '.$target.','}}

für Sie wurde oder wird demnächst durch Ihre IT-Abteilung ein neues Gerät ausgegeben.

@if ($req_accept == 1)
Bitte bestätigen Sie den Empfang nach Erhalt des Geräts.
@endif

@if (($snipeSettings->show_images_in_email =='1') && $item->getImageUrl())
<center><img src="{{ $item->getImageUrl() }}" alt="Asset" style="max-width: 570px;"></center>
@endif

@component('mail::table')
|        |          |
| ------------- | ------------- |
@if ((isset($item->name)) && ($item->name!=''))
| **{{ 'Gerätename' }}** | {{ $item->name }} |
@endif
@if (($item->name!=$item->asset_tag))
| **{{ 'Inventarnummer'  }}** | {{ $item->asset_tag }} |
@endif
@if (isset($item->manufacturer))
| **{{ 'Hersteller' }}** | {{ $item->manufacturer->name }} |
@endif
@if (isset($item->model))
| **{{ 'Gerätemodell' }}** | {{ $item->model->name }} |
@endif
@if ((isset($item->model->model_number)) && ($item->model->name!=$item->model->model_number))
| **{{ trans('general.model_no') }}** | {{ $item->model->model_number }} |
@endif
@if (isset($item->serial))
| **{{ trans('mail.serial') }}** | {{ $item->serial }} |
@endif
@if (isset($last_checkout))
| **{{ 'Zuweisungsdatum' }}** | {{ $last_checkout }} |
@endif
@if ((isset($expected_checkin)) && ($expected_checkin!=''))
| **{{ trans('mail.expecting_checkin_date') }}** | {{ $expected_checkin }} |
@endif
@if (!empty($custom_fields))
@foreach($custom_fields as $customField)
@if (!empty($customField['label']) && array_key_exists('value', $customField) && $customField['value'] !== '')
| **{{ $customField['label'] }}** | {{ $customField['value'] }} |
@endif
@endforeach
@endif
@if ($admin)
| **{{ 'IT-Mitarbeiter' }}** | {{ $admin->display_name }} |
@endif
@if ($note)
| **{{ trans('mail.additional_notes') }}** | {{ $note }} |
@endif
@endcomponent

@if ($req_accept == 1)
Bitte lesen Sie die Nutzungsbedingungen unten, klicken Sie auf den Link am unteren Ende, um zu bestätigen, dass Sie die Nutzungsbedingungen gelesen und akzeptiert haben, sowie das Gerät erhalten haben.
@endif

@if ($eula)
@component('mail::panel')
{!! $eula !!}
@endcomponent
@endif

@if ($req_accept == 1)

**[✔ {{ 'Ich habe die Nutzungsbedingungen gelesen und stimme diesen zu, und habe dieses Gerät erhalten.' }}]({{ $accept_url }})**
@endif


Mit freundlichen Grüßen

Ihre IT-Abteilung

{{ $snipeSettings->site_name }}

@endcomponent
