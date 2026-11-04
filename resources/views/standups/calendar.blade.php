@extends('layouts.app')
@section('title', 'Pick a Standup Date')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('standups.index') }}" class="hover:text-violet-600 transition-colors flex items-center gap-1">
                    <i class="bi bi-arrow-left text-xs"></i> Standups
                </a>
            </div>
            <h1 class="page-title">Select Date</h1>
            <p class="text-sm text-slate-500 mt-0.5">Choose a date to view or manage standups.</p>
        </div>
    </div>

    {{-- Calendar --}}
    <div x-data="standupCalendar()" class="card select-none">

        {{-- Month navigation --}}
        <div class="flex items-center justify-between mb-5">
            <button @click="prevMonth()"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 hover:text-violet-600
                           hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-chevron-left text-sm"></i>
            </button>
            <h2 class="font-bold text-slate-900 dark:text-white text-base" x-text="monthLabel"></h2>
            <button @click="nextMonth()" :disabled="isCurrentMonth"
                    :class="isCurrentMonth ? 'opacity-30 cursor-not-allowed' : 'hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20'"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 transition-colors">
                <i class="bi bi-chevron-right text-sm"></i>
            </button>
        </div>

        {{-- Day-of-week headers --}}
        <div class="grid grid-cols-7 mb-2">
            <template x-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d">
                <div class="text-center text-xs font-bold text-slate-400 uppercase tracking-wide py-1" x-text="d"></div>
            </template>
        </div>

        {{-- Day grid --}}
        <div class="grid grid-cols-7 gap-1">
            <template x-for="cell in calendarCells" :key="cell.key">
                <div>
                    {{-- Empty cell (padding) --}}
                    <template x-if="!cell.date">
                        <div class="aspect-square"></div>
                    </template>

                    {{-- Day cell --}}
                    <template x-if="cell.date">
                        <button
                            type="button"
                            @click="cell.isFuture ? null : selectDate(cell.dateStr)"
                            :disabled="cell.isFuture"
                            :class="{
                                'bg-violet-600 text-white font-bold shadow-sm shadow-violet-200': cell.isToday,
                                'ring-2 ring-violet-400 ring-offset-1': cell.isSelected && !cell.isToday,
                                'opacity-30 cursor-not-allowed': cell.isFuture,
                                'hover:bg-violet-50 dark:hover:bg-violet-900/20 hover:text-violet-700 cursor-pointer': !cell.isFuture && !cell.isToday,
                                'text-slate-400': cell.isWeekend && !cell.isToday,
                                'text-slate-700 dark:text-slate-200': !cell.isWeekend && !cell.isToday,
                            }"
                            class="aspect-square w-full rounded-xl text-sm flex items-center justify-center font-medium transition-all"
                            x-text="cell.day">
                        </button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <button @click="selectDate(todayStr)"
                    class="btn-ghost btn-sm text-violet-600 dark:text-violet-400">
                <i class="bi bi-calendar-check text-sm"></i> Jump to today
            </button>
            <p class="text-xs text-slate-400">Dates after today are disabled</p>
        </div>
    </div>

</div>

<script>
function standupCalendar() {
    const params     = new URLSearchParams(window.location.search);
    const returnDate = params.get('return_date') || null;
    const today      = new Date();
    today.setHours(0, 0, 0, 0);
    const todayStr = today.toISOString().split('T')[0];

    // Start from the month of return_date (or today)
    const startDate = returnDate ? new Date(returnDate + 'T00:00:00') : today;

    return {
        year:        startDate.getFullYear(),
        month:       startDate.getMonth(), // 0-indexed
        selectedStr: returnDate || todayStr,
        todayStr,

        get monthLabel() {
            return new Date(this.year, this.month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        get isCurrentMonth() {
            const now = new Date();
            return this.year === now.getFullYear() && this.month === now.getMonth();
        },

        get calendarCells() {
            const firstDay  = new Date(this.year, this.month, 1);
            const lastDay   = new Date(this.year, this.month + 1, 0);
            const startDow  = firstDay.getDay(); // 0 = Sun
            const cells     = [];

            // Leading empty cells
            for (let i = 0; i < startDow; i++) {
                cells.push({ key: `e${i}`, date: null });
            }

            for (let d = 1; d <= lastDay.getDate(); d++) {
                const dt     = new Date(this.year, this.month, d);
                const dStr   = dt.toISOString().split('T')[0];
                cells.push({
                    key:        dStr,
                    date:       dt,
                    dateStr:    dStr,
                    day:        d,
                    isToday:    dStr === this.todayStr,
                    isSelected: dStr === this.selectedStr,
                    isFuture:   dt > today,
                    isWeekend:  dt.getDay() === 0 || dt.getDay() === 6,
                });
            }

            return cells;
        },

        prevMonth() {
            if (this.month === 0) { this.year--; this.month = 11; }
            else this.month--;
        },
        nextMonth() {
            if (this.isCurrentMonth) return;
            if (this.month === 11) { this.year++; this.month = 0; }
            else this.month++;
        },

        selectDate(dateStr) {
            this.selectedStr = dateStr;
            window.location.href = `/standups?date=${dateStr}`;
        },
    };
}
</script>
@endsection
