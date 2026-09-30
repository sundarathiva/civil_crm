<label class="field {{ !empty($wide) ? 'wide' : '' }}">
    <span>{{ $label }}</span>
    @if (($as ?? 'input') === 'textarea')
        <textarea name="{{ $name }}" rows="{{ $rows ?? 4 }}">{{ $value ?? '' }}</textarea>
    @elseif (($as ?? 'input') === 'select')
        <select name="{{ $name }}" @if(!empty($required)) required @endif @if(!empty($id)) id="{{ $id }}" @endif>
            @if (!empty($placeholder))
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $key => $text)
                <option value="{{ $key }}" @selected((string) ($value ?? '') === (string) $key)>{{ $text }}</option>
            @endforeach
        </select>
    @else
        <input
            type="{{ $type ?? 'text' }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            @if(isset($step)) step="{{ $step }}" @endif
            @if(isset($min)) min="{{ $min }}" @endif
            @if(isset($max)) max="{{ $max }}" @endif
            @if(!empty($required)) required @endif
            @if(!empty($id)) id="{{ $id }}" @endif
        >
    @endif
    @error($name)<small>{{ $message }}</small>@enderror
</label>
