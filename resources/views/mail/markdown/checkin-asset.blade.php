@component('mail::message')
# {{ trans('mail.hello') }} {{ $target->display_name }},

folgendes Gerät wurde durch die IT-Abteilung von Ihnen zurückgenommen:

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
| **{{ 'Inventarnummer' }}** | {{ $item->asset_tag }} |
@endif
@if (isset($item->model->category))
| **{{ trans('general.category') }}** | {{ $item->model->category->name }} |
@endif
@if (isset($item->manufacturer))
| **{{ 'Hersteller' }}** | {{ $item->manufacturer->name }} |
@endif
@if (isset($item->model))
| **{{ trans('general.asset_model') }}** | {{ $item->model->name }} |
@endif
@if ((isset($item->model->model_number)) && ($item->model->name!=$item->model->model_number))
| **{{ trans('general.model_no') }}** | {{ $item->model->model_number }} |
@endif
@if (isset($item->serial))
| **{{ trans('mail.serial') }}** | {{ $item->serial }} |
@endif
@if (isset($last_checkout))
| **{{ trans('mail.checkout_date') }}** | {{ $last_checkout }} |
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

Mit freundlichen Grüßen

Ihre IT-Abteilung

{{ $snipeSettings->site_name }}

@endcomponent
