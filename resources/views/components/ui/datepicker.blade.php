@props([
    'name'        => 'date',
    'value'       => '',
    'placeholder' => 'Select date',
    'id'          => null,
])

@php
    $inputId = $id ?? ('dp-' . $name . '-' . uniqid());
@endphp

<div x-data="datepicker(@js($value))"
     x-init="init()"
     @click.outside="open = false"
     class="relative">

    {{-- Hidden real input for form submission --}}
    <input type="hidden" name="{{ $name }}" :value="selected" @if($id) id="{{ $inputId }}" @endif>

    {{-- Trigger button --}}
    <button type="button"
            @click="open = !open"
            :class="open ? 'ring-2 ring-violet-500/30 border-violet-500 dark:border-violet-400' : ''"
            class="form-input text-left flex items-center gap-2 w-full">
        <i class="bi bi-calendar3 text-slate-400 flex-shrink-0 text-sm"></i>
        <span :class="selected ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'"
              x-text="selected ? formatDisplay(selected) : @js($placeholder)"></span>
        <i class="bi bi-chevron-down ml-auto text-slate-400 transition-transform duration-150 flex-shrink-0"
           :class="open ? 'rotate-180' : ''" style="font-size:0.65rem"></i>
    </button>

    {{-- Calendar dropdown --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute z-50 mt-1 w-72 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl p-3">

        {{-- Month / Year navigation --}}
        <div class="flex items-center justify-between mb-3">
            <button type="button" @click="prevMonth()"
                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class="bi bi-chevron-left text-xs"></i>
            </button>
            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200"
                  x-text="monthLabel()"></span>
            <button type="button" @click="nextMonth()"
                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class="bi bi-chevron-right text-xs"></i>
            </button>
        </div>

        {{-- Day-of-week headers --}}
        <div class="grid grid-cols-7 mb-1">
            <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                <div class="text-center text-xs font-medium text-slate-400 dark:text-slate-500 py-1" x-text="d"></div>
            </template>
        </div>

        {{-- Day grid --}}
        <div class="grid grid-cols-7 gap-y-0.5">
            {{-- Leading empty cells --}}
            <template x-for="_ in Array(startDay())" :key="'e'+_">
                <div></div>
            </template>
            {{-- Day buttons --}}
            <template x-for="day in daysInMonth()" :key="day">
                <button type="button"
                        @click="selectDay(day)"
                        :class="{
                            'bg-violet-600 text-white font-semibold hover:bg-violet-700': isSelected(day),
                            'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 font-medium': isToday(day) && !isSelected(day),
                            'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800': !isSelected(day) && !isToday(day),
                        }"
                        class="text-xs h-8 w-full rounded-lg transition-colors"
                        x-text="day"></button>
            </template>
        </div>

        {{-- Today shortcut --}}
        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <button type="button" @click="goToday()"
                    class="text-xs text-violet-500 hover:text-violet-700 font-medium">Today</button>
            <button type="button" @click="selected = ''; open = false"
                    class="text-xs text-slate-400 hover:text-slate-600">Clear</button>
        </div>
    </div>
</div>

<script>
if (typeof datepicker === 'undefined') {
function datepicker(initial) {
    return {
        open:    false,
        selected: initial || '',
        viewYear:  0,
        viewMonth: 0,

        init() {
            const base = this.selected ? new Date(this.selected + 'T00:00:00') : new Date();
            this.viewYear  = base.getFullYear();
            this.viewMonth = base.getMonth();
        },

        formatDisplay(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-').map(Number);
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return months[m - 1] + ' ' + d + ', ' + y;
        },

        monthLabel() {
            const months = ['January','February','March','April','May','June',
                            'July','August','September','October','November','December'];
            return months[this.viewMonth] + ' ' + this.viewYear;
        },

        daysInMonth() {
            return new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
        },

        startDay() {
            return new Date(this.viewYear, this.viewMonth, 1).getDay();
        },

        prevMonth() {
            if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; }
            else { this.viewMonth--; }
        },

        nextMonth() {
            if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; }
            else { this.viewMonth++; }
        },

        padded(n) { return String(n).padStart(2, '0'); },

        iso(day) {
            return this.viewYear + '-' + this.padded(this.viewMonth + 1) + '-' + this.padded(day);
        },

        selectDay(day) {
            this.selected = this.iso(day);
            this.open = false;
        },

        isSelected(day) { return this.selected === this.iso(day); },

        isToday(day) {
            const t = new Date();
            return t.getFullYear() === this.viewYear &&
                   t.getMonth()    === this.viewMonth &&
                   t.getDate()     === day;
        },

        goToday() {
            const t = new Date();
            this.viewYear  = t.getFullYear();
            this.viewMonth = t.getMonth();
            this.selected  = t.getFullYear() + '-' + this.padded(t.getMonth() + 1) + '-' + this.padded(t.getDate());
            this.open = false;
        },
    };
}
}
</script>
