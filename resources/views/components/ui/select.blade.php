@props([
    'name'        => null,
    'value'       => '',
    'options'     => [],   // assoc ['val'=>'Label'] or indexed ['Label'] or [['value'=>v,'label'=>l]]
    'placeholder' => 'Select…',
    'required'    => false,
    'size'        => 'default',  // 'sm'
    'id'          => null,
])

@php
$isList = is_array($options) && array_is_list($options);
$opts = collect($options)->map(function ($label, $value) use ($isList) {
    if ($isList) {
        // Sequential indexed array: value === label (e.g. ['HR', 'Engineering'])
        return ['value' => (string) $label, 'label' => (string) $label];
    }
    if (is_array($label)) {
        return ['value' => (string) ($label['value'] ?? $value), 'label' => (string) ($label['label'] ?? $value)];
    }
    return ['value' => (string) $value, 'label' => (string) $label];
})->values()->toArray();

$currentValue = (string) $value;
$currentLabel = $placeholder;
foreach ($opts as $opt) {
    if ($opt['value'] === $currentValue && $currentValue !== '') {
        $currentLabel = $opt['label'];
        break;
    }
}

$sizeClasses = $size === 'sm' ? 'py-1.5 px-3 text-xs' : '';
@endphp

<div x-data="{
        open: false,
        value: @js($currentValue),
        label: @js($currentLabel),
        opts:  @js($opts),
        placeholder: @js($placeholder),
        select(v, l) {
            this.value = v;
            this.label = l || v;
            this.open  = false;
            this.$el.dispatchEvent(new CustomEvent('change', { detail: v, bubbles: true }));
        }
     }"
     x-modelable="value"
     @click.outside="open = false"
     {{ $attributes->except(['name','value','options','placeholder','required','size','id'])->class(['relative']) }}>

    @if($name)
    <input type="hidden" name="{{ $name }}" :value="value"
           @if($id) id="{{ $id }}" @endif
           @if($required) x-effect="value ? $el.removeAttribute('required') : $el.setAttribute('required','')" @endif>
    @endif

    {{-- Trigger button --}}
    <button type="button"
            @click="open = !open"
            :class="open ? 'ring-2 ring-violet-500/30 border-violet-500 dark:border-violet-400' : ''"
            class="form-input text-left flex items-center justify-between gap-2 w-full {{ $sizeClasses }}">
        <span :class="value === '' ? 'text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-slate-100'"
              x-text="label"></span>
        <i class="bi bi-chevron-down flex-shrink-0 text-slate-400 transition-transform duration-150"
           :class="open ? 'rotate-180' : ''"
           style="font-size:0.65rem"></i>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         style="z-index:200"
         class="absolute mt-1 w-full min-w-max bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden max-h-60 overflow-y-auto">

        {{-- Placeholder row --}}
        @if($placeholder)
        <button type="button"
                @click="select('', placeholder)"
                :class="value === '' ? 'text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-900/10 font-medium' : 'text-slate-400 dark:text-slate-500'"
                class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors">
            <span>{{ $placeholder }}</span>
            <i x-show="value === ''" class="bi bi-check2 text-violet-500 text-xs ml-2"></i>
        </button>
        @endif

        {{-- Option rows --}}
        <template x-for="opt in opts" :key="opt.value">
            <button type="button"
                    @click="select(opt.value, opt.label)"
                    :class="value === opt.value
                        ? 'text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-900/10 font-medium'
                        : 'text-slate-700 dark:text-slate-200'"
                    class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors">
                <span x-text="opt.label"></span>
                <i x-show="value === opt.value" class="bi bi-check2 text-violet-500 text-xs ml-2"></i>
            </button>
        </template>
    </div>
</div>
