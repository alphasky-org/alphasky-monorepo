
<x-core::form.field
    :showLabel="$showLabel"
    :showField="$showField"
    :options="$options"
    :name="$name"
    :prepend="$prepend ?? null"
    :append="$append ?? null"
    :showError="$showError"
    :nameKey="$nameKey"
>
    <x-slot:label>
        @if ($showLabel && $options['label'] !== false && $options['label_show'])
            {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
        @endif
    </x-slot:label>

  

    <fieldset class="leform-star-rating leform-star-rating-small">
        <input type="radio" name="{{$name}}" id="star{{$name}}-5" value="5" @if($options['value'] == 5) checked @endif data-default="off">
        <label class="noSwipe" for="star{{$name}}-5"></label>

            <input type="radio" name="{{$name}}" id="star{{$name}}-4" value="4" @if($options['value'] == 4) checked @endif data-default="off">
            <label class="noSwipe" for="star{{$name}}-4"></label>
            
            <input type="radio" name="{{$name}}" id="star{{$name}}-3" value="3" @if($options['value'] == 3) checked @endif data-default="off">
            <label class="noSwipe" for="star{{$name}}-3"></label>
            
            <input type="radio" name="{{$name}}" id="star{{$name}}-2" value="2" @if($options['value'] == 2) checked @endif data-default="off">
            <label class="noSwipe" for="star{{$name}}-2"></label>
            
            <input type="radio" name="{{$name}}" id="star{{$name}}-1" value="1" @if($options['value'] == 1) checked @endif data-default="off">
            <label class="noSwipe" for="star{{$name}}-1"></label>
    </fieldset>
</x-core::form.field>
 
    <style>
        .leform-star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .leform-star-rating input {
            display: none;
        }
        .leform-star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #ccc;
        }
        .leform-star-rating input:checked ~ label {
            color: gold;
        }
        .leform-star-rating label:hover,
        .leform-star-rating label:hover ~ label {
            color: gold;
        }
        /*fontawesome icon*/
        .leform-star-rating label::before {
            content: "\f005"; /* Unicode for FontAwesome star */
            font-family: "Font Awesome 5 Free"; /* Ensure you have FontAwesome included */
            font-weight: 900; /* Use 900 for solid stars */
        }
    </style>