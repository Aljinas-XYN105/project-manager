<template>
  <div class="flex h-full overflow-hidden">

    <!-- Mobile sidebar overlay -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="mobileSidebar" class="mobile-nav-overlay" @click="mobileSidebar = false"></div>
    </Transition>

    <!-- SIDEBAR -->
    <aside
      :class="[
        sidebarOpen ? 'w-[220px]' : 'w-[56px]',
        mobileSidebar ? 'translate-x-0 !w-[220px]' : '-translate-x-full lg:translate-x-0'
      ]"
      class="fixed inset-y-0 left-0 z-50 flex flex-col w-[220px]
             -translate-x-full lg:translate-x-0
             bg-white dark:bg-[#0C0E13] text-slate-800 dark:text-white
             transition-all duration-250 ease-in-out
             border-r border-slate-200 dark:border-white/[0.05] lg:relative lg:inset-auto flex-shrink-0"
    >
      <!-- Workspace Header -->
      <div class="flex items-center h-14 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0 gap-2.5"
           :class="sidebarOpen || mobileSidebar ? 'px-3' : 'px-0 justify-center'">
        <!-- Expanded: full logo -->
        <template v-if="sidebarOpen || mobileSidebar">
          <div class="min-w-0 flex-1 flex items-center">
            <img src="/images/workzen-logo.png" alt="Workzen"
                 class="h-9 w-auto dark:hidden" style="object-fit:contain; object-position:left center;">
            <p class="hidden dark:block font-bold text-[15px] text-white/90 truncate leading-none tracking-tight">Workzen</p>
          </div>
          <button @click="sidebarOpen = !sidebarOpen"
                  class="hidden lg:flex ml-auto w-6 h-6 items-center justify-center rounded-lg
                         text-slate-400 hover:text-slate-700 hover:bg-slate-100
                         dark:text-white/20 dark:hover:text-white/60 dark:hover:bg-white/[0.07]
                         transition-all duration-150 flex-shrink-0">
            <i class="bi bi-chevron-double-left text-[10px]"></i>
          </button>
        </template>
        <!-- Collapsed: W icon only -->
        <template v-else>
          <button @click="sidebarOpen = true"
                  class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0
                         hover:opacity-80 transition-opacity"
                  title="Expand sidebar">
            <img src="/images/workzen-icon.webp" alt="Workzen" class="h-8 w-auto">
          </button>
        </template>
      </div>

      <!-- Quick Add Button -->
      <div v-if="sidebarOpen || mobileSidebar"
           class="px-2.5 py-2.5 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0">
        <Link :href="route('tasks.create')"
              class="flex items-center w-full justify-start px-3 gap-2.5 py-2 rounded-xl
                     bg-violet-50 hover:bg-violet-100 dark:bg-violet-600/15 dark:hover:bg-violet-600/25
                     border border-violet-200 hover:border-violet-300 dark:border-violet-500/25 dark:hover:border-violet-500/40
                     text-violet-600 hover:text-violet-700 dark:text-violet-300 dark:hover:text-violet-200
                     transition-all duration-150 text-[13px] font-semibold active:scale-[0.98]">
          <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
            <i class="bi bi-plus-lg text-sm"></i>
          </div>
          <span class="truncate">New Task</span>
          <kbd class="ml-auto text-[10px] font-mono text-violet-400/60 bg-violet-100 dark:bg-violet-900/30 px-1.5 py-0.5 rounded-md hidden sm:inline">⌘K</kbd>
        </Link>
      </div>
      <!-- Collapsed icon-only -->
      <div v-else
           class="px-2.5 py-2.5 border-b border-slate-200 dark:border-white/[0.05] flex-shrink-0">
        <button @click="quickAdd = true"
                class="flex items-center w-full justify-center px-0 py-2 rounded-xl
                       bg-violet-50 hover:bg-violet-100 dark:bg-violet-600/15 dark:hover:bg-violet-600/25
                       border border-violet-200 dark:border-violet-500/25
                       text-violet-600 dark:text-violet-300 hover:text-violet-700 dark:hover:text-violet-200
                       transition-all duration-150 active:scale-[0.98]"
                title="New Task (⌘K)">
          <i class="bi bi-plus-lg text-sm"></i>
        </button>
      </div>

      <!-- Nav -->
      <nav ref="navEl" class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5 scrollbar-hide">

        <template v-for="(group, groupIdx) in navGroups" :key="group.name">
          <div :class="groupIdx > 0 ? 'mt-4' : 'mt-1'">
            <div v-if="sidebarOpen || mobileSidebar"
                 class="px-2.5 mb-1 flex items-center gap-1.5">
              <span class="text-[9px] font-bold tracking-[0.12em] uppercase" :class="group.color">{{ group.name }}</span>
              <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]"></div>
            </div>

            <Link v-for="item in group.items" :key="item.route"
                  :href="route(item.route)"
                  class="sidebar-link relative flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                         transition-all duration-150 group select-none"
                  :class="isActive(item.match)
                    ? 'bg-violet-100 dark:bg-violet-600/15 text-violet-700 dark:text-white sidebar-nav-active'
                    : 'text-slate-500 dark:text-white/40 hover:text-slate-900 dark:hover:text-white/80 hover:bg-slate-100 dark:hover:bg-white/[0.055]'"
                  :title="sidebarOpen || mobileSidebar ? '' : item.label"
                  @click="mobileSidebar = false"
            >
              <span v-if="isActive(item.match)" class="absolute left-0 top-[18%] h-[64%] w-[3px] bg-violet-500 rounded-r-full"></span>
              <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center rounded-lg transition-all duration-150"
                    :class="isActive(item.match) ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400 dark:text-white/35 group-hover:text-slate-700 dark:group-hover:text-white/70'">
                <i class="bi text-[14px]" :class="item.icon"></i>
              </span>
              <span v-if="sidebarOpen || mobileSidebar" class="flex-1 truncate leading-none">{{ item.label }}</span>
              <template v-if="item.badge && navBadges[item.badge] > 0">
                <span v-if="sidebarOpen || mobileSidebar"
                      class="ml-auto min-w-[18px] h-[18px] px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                  {{ navBadges[item.badge] }}
                </span>
                <span v-else class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
              </template>
            </Link>
          </div>
        </template>

        <!-- Admin section -->
        <div v-if="adminLinks.length" class="mt-4">
          <div v-if="sidebarOpen || mobileSidebar" class="px-2.5 mb-1 flex items-center gap-1.5">
            <span class="text-[9px] font-bold tracking-[0.12em] uppercase text-rose-400 dark:text-rose-300/50">ADMIN</span>
            <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]"></div>
          </div>
          <Link v-for="link in adminLinks" :key="link.route"
                :href="route(link.route)"
                class="relative flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                       transition-all duration-150 group select-none"
                :class="isActive(link.match)
                  ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-200 sidebar-nav-active'
                  : 'text-slate-500 dark:text-white/40 hover:text-slate-900 dark:hover:text-white/80 hover:bg-slate-100 dark:hover:bg-white/[0.055]'"
                :title="sidebarOpen || mobileSidebar ? '' : link.label"
                @click="mobileSidebar = false"
          >
            <span v-if="isActive(link.match)" class="absolute left-0 top-[18%] h-[64%] w-[3px] bg-rose-500 rounded-r-full"></span>
            <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center rounded-lg"
                  :class="isActive(link.match) ? 'text-rose-500 dark:text-rose-400' : 'text-slate-400 dark:text-white/35 group-hover:text-rose-500 dark:group-hover:text-rose-300/70'">
              <i class="bi text-[14px]" :class="link.icon"></i>
            </span>
            <span v-if="sidebarOpen || mobileSidebar" class="flex-1 truncate leading-none">{{ link.label }}</span>
          </Link>
        </div>
      </nav>

      <!-- Bottom section -->
      <div class="px-2 pt-2 pb-3 border-t border-slate-200 dark:border-white/[0.05] flex-shrink-0 space-y-0.5">
        <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100">
          <Link v-if="sidebarOpen || mobileSidebar"
                :href="route('daily-reports.create')"
                class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-[12px] font-medium
                       bg-violet-50 hover:bg-violet-100 dark:bg-violet-500/8 dark:hover:bg-violet-500/15
                       border border-violet-200 hover:border-violet-300 dark:border-violet-500/15 dark:hover:border-violet-500/25
                       text-violet-600 hover:text-violet-700 dark:text-violet-300/70 dark:hover:text-violet-200
                       transition-all duration-150 mb-1">
            <i class="bi bi-journal-plus text-sm flex-shrink-0"></i>
            <span class="truncate">Submit Daily Report</span>
            <i class="bi bi-arrow-right-short ml-auto text-violet-400 dark:text-violet-400/40 flex-shrink-0"></i>
          </Link>
        </Transition>

        <Link :href="route('settings.profile')"
              class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium
                     text-slate-500 dark:text-white/35 hover:text-slate-900 dark:hover:text-white/75
                     hover:bg-slate-100 dark:hover:bg-white/[0.055] transition-all duration-150"
              :title="sidebarOpen || mobileSidebar ? '' : 'Settings'">
          <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center">
            <i class="bi bi-gear text-[14px]"></i>
          </span>
          <span v-if="sidebarOpen || mobileSidebar" class="truncate leading-none">Settings</span>
        </Link>

        <Link :href="route('help')"
              class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-xl text-[13px] font-medium transition-all duration-150"
              :class="isActive('help') ? 'bg-slate-100 dark:bg-white/[0.09] text-slate-800 dark:text-white/80' : 'text-slate-500 dark:text-white/35 hover:text-slate-900 dark:hover:text-white/75 hover:bg-slate-100 dark:hover:bg-white/[0.055]'"
              :title="sidebarOpen || mobileSidebar ? '' : 'Help & Docs'">
          <span class="w-[26px] h-[26px] flex-shrink-0 flex items-center justify-center">
            <i class="bi bi-question-circle text-[14px]"></i>
          </span>
          <span v-if="sidebarOpen || mobileSidebar" class="truncate leading-none">Help & Docs</span>
        </Link>

        <!-- Expand toggle (collapsed state) -->
        <button v-if="!sidebarOpen && !mobileSidebar"
                @click="sidebarOpen = !sidebarOpen"
                class="hidden lg:flex w-full items-center justify-center py-2 rounded-xl
                       text-slate-400 hover:text-slate-700 hover:bg-slate-100
                       dark:text-white/20 dark:hover:text-white/60 dark:hover:bg-white/[0.06]
                       transition-all duration-150"
                title="Expand sidebar">
          <i class="bi bi-chevron-double-right text-xs"></i>
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

      <!-- Header -->
      <header class="h-14 flex items-center gap-3 px-4 lg:px-5 flex-shrink-0
                     bg-white/90 dark:bg-slate-950/90 backdrop-blur-xl
                     border-b border-slate-200/60 dark:border-white/[0.06]
                     sticky top-0 z-30">

        <!-- Mobile menu button -->
        <button @click="mobileSidebar = !mobileSidebar"
                class="lg:hidden btn-icon btn-ghost -ml-1">
          <i class="bi bi-list text-lg"></i>
        </button>

        <!-- Global search -->
        <div ref="searchContainerRef" class="relative flex-1 max-w-sm">
          <i class="bi absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 text-xs pointer-events-none"
             :class="searchLoading ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i>
          <input
            ref="searchInput"
            type="search"
            v-model="searchQ"
            @input="onSearchInput"
            @focus="searchGroups.length && (searchOpen = true)"
            @keydown.escape="searchOpen = false; ($event.target).blur()"
            @keydown.arrow-down.prevent="moveSearch(1)"
            @keydown.arrow-up.prevent="moveSearch(-1)"
            @keydown.enter.prevent="goSearch"
            autocomplete="off"
            placeholder="Search…  ( / )"
            class="w-full pl-8 pr-3 py-2 text-sm rounded-xl
                   bg-slate-100/80 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100
                   border border-transparent focus:border-slate-300 dark:focus:border-slate-700
                   focus:bg-white dark:focus:bg-slate-800 focus:ring-0
                   placeholder-slate-400 dark:placeholder-slate-600
                   outline-none transition-all duration-150"
          >
          <Transition enter-active-class="transition-opacity duration-100" enter-from-class="opacity-0" enter-to-class="opacity-100">
            <div v-if="searchOpen"
                 class="dropdown-menu absolute left-0 right-0 top-full mt-2 max-h-[26rem] overflow-y-auto py-1">
              <p v-if="!searchGroups.length" class="px-4 py-6 text-center text-xs text-slate-400">No matches</p>
              <template v-for="g in searchGroups" :key="g.label">
                <div>
                  <p class="px-3.5 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ g.label }}</p>
                  <a v-for="item in g.items" :key="item.url"
                     :href="item.url"
                     class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"
                     :class="searchFlat[searchActive] === item ? 'bg-violet-50 dark:bg-violet-900/20' : ''">
                    <img v-if="item.avatar" :src="item.avatar" class="w-6 h-6 rounded-full" alt="">
                    <span v-if="item.color" class="w-2.5 h-2.5 rounded-full" :style="'background:' + item.color"></span>
                    <span class="min-w-0">
                      <span class="block text-sm text-slate-800 dark:text-slate-200 truncate">{{ item.title }}</span>
                      <span class="block text-[11px] text-slate-400 truncate">{{ item.subtitle }}</span>
                    </span>
                  </a>
                </div>
              </template>
              <a :href="route('search') + '?q=' + encodeURIComponent(searchQ)"
                 class="w-full text-left px-3.5 py-2 mt-1 border-t border-slate-100 dark:border-slate-800 text-xs text-violet-600 hover:bg-slate-50 dark:hover:bg-slate-800 block">
                See all results for "{{ searchQ }}" →
              </a>
            </div>
          </Transition>
        </div>

        <!-- Header actions -->
        <div class="flex items-center gap-0.5 ml-auto">

          <!-- Quick add (mobile) -->
          <button @click="quickAdd = true" class="lg:hidden btn-icon btn-ghost" title="New Task">
            <i class="bi bi-plus-lg text-base"></i>
          </button>

          <!-- Dark mode -->
          <button @click="toggleDark" class="btn-icon btn-ghost" :title="darkMode ? 'Light mode' : 'Dark mode'">
            <i :class="darkMode ? 'bi-sun-fill text-amber-400' : 'bi-moon-fill text-slate-400'" class="bi text-[15px]"></i>
          </button>

          <!-- Presence widget -->
          <div ref="presenceContainerRef" class="relative">
            <button @click.stop="togglePresence" :disabled="presenceBusy"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-150
                           text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                    :class="presenceOpen ? 'bg-slate-100 dark:bg-slate-800' : ''">
              <span class="w-2 h-2 rounded-full ring-2 ring-white dark:ring-slate-950 flex-shrink-0" :class="currentPresence.dot"></span>
              <span class="hidden sm:inline truncate max-w-[80px]">{{ currentPresence.label }}</span>
              <span v-if="presenceClockedIn" class="hidden md:inline font-mono tabular-nums text-slate-400 text-[11px]">{{ presenceElapsed }}</span>
              <i class="bi bi-chevron-down text-[9px] text-slate-400"></i>
            </button>
            <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
              <div v-if="presenceOpen"
                   class="dropdown-menu absolute right-0 top-full mt-2 w-64">
                <div class="px-3.5 py-3 border-b border-slate-100 dark:border-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Today's attendance</p>
                  <div class="flex items-center justify-between mt-2">
                    <div>
                      <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ presenceClockedIn ? '🟢 Clocked in' : '⭕ Clocked out' }}</p>
                      <p v-if="presenceClockedIn && presenceClockInAt" class="text-xs text-slate-500 mt-0.5">
                        since {{ formatClockIn(presenceClockInAt) }}
                      </p>
                    </div>
                    <span class="font-mono text-sm font-bold text-slate-800 dark:text-white tabular-nums">{{ presenceElapsed }}</span>
                  </div>
                  <button @click="toggleClock" :disabled="presenceBusy"
                          class="mt-3 w-full btn-sm justify-center font-semibold"
                          :class="presenceClockedIn ? 'btn-secondary' : 'btn-primary'">
                    <i class="bi" :class="presenceClockedIn ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right'"></i>
                    {{ presenceClockedIn ? 'Clock out' : 'Clock in' }}
                  </button>
                </div>
                <div class="py-1">
                  <p class="px-3.5 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Set status</p>
                  <button v-for="(s, key) in presenceStatuses" :key="key"
                          @click="setPresenceStatus(key)"
                          class="dropdown-item w-full">
                    <span class="w-2 h-2 rounded-full" :class="s.dot"></span>
                    <span class="flex-1 text-left text-sm">{{ s.label }}</span>
                    <i v-if="presenceAvailability === key" class="bi bi-check2 text-violet-600 text-xs"></i>
                  </button>
                </div>
                <div class="dropdown-divider"></div>
                <Link :href="route('attendance.index')" class="dropdown-item text-sm" @click="presenceOpen = false">
                  <i class="bi bi-calendar-check text-slate-400 text-sm"></i> My attendance history
                </Link>
              </div>
            </Transition>
          </div>

          <!-- Multi-timer widget -->
          <div ref="timerContainerRef" class="relative">
            <button @click.stop="timerOpen = !timerOpen"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-150 relative"
                    :class="timerHasActive
                      ? (timerRunningCount > 0
                          ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-800'
                          : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800')
                      : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-400'"
                    :title="timerHasActive ? timerEntries.length + ' active timer(s)' : 'Track time'">
              <i class="bi text-sm"
                 :class="timerHasActive
                   ? (timerRunningCount > 0 ? 'bi-stopwatch-fill animate-pulse' : 'bi-pause-circle-fill')
                   : 'bi-stopwatch'"></i>
              <span v-if="timerHasActive && timerRunningCount > 0 && timerEntries.length === 1"
                    class="font-mono text-[11px] tabular-nums hidden sm:inline">
                {{ timerEntries[0]?.elapsed_formatted }}
              </span>
              <span v-if="timerHasActive && timerEntries.length > 1" class="hidden sm:inline">{{ timerEntries.length }} timers</span>
              <span v-if="!timerHasActive" class="hidden sm:block">Timer</span>
              <span v-if="timerHasActive"
                    class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-950"
                    :class="timerRunningCount > 0 ? 'bg-emerald-500' : 'bg-amber-400'"></span>
            </button>
            <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
              <div v-if="timerOpen" class="dropdown-menu absolute right-0 top-full mt-2 w-80 z-50">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">Active Timers</span>
                  <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span v-if="timerRunningCount > 0" class="text-emerald-600 dark:text-emerald-400">
                      <i class="bi bi-play-circle-fill"></i> {{ timerRunningCount }}
                    </span>
                    <span v-if="timerPausedCount > 0" class="text-amber-500 dark:text-amber-400">
                      <i class="bi bi-pause-circle-fill"></i> {{ timerPausedCount }}
                    </span>
                  </div>
                </div>
                <div v-if="!timerHasActive" class="px-4 py-8 text-center">
                  <i class="bi bi-stopwatch text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                  <p class="text-sm text-slate-500 dark:text-slate-400">No active timers</p>
                  <Link :href="route('time-entries.index')" class="mt-3 inline-block text-xs text-violet-600 dark:text-violet-400 hover:underline" @click="timerOpen = false">
                    Go to time tracking
                  </Link>
                </div>
                <div v-else class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                  <div v-for="entry in timerEntries" :key="entry.id" class="px-4 py-3 flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full flex-shrink-0"
                          :class="entry.status === 'running' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></span>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ entry.task_title }}</p>
                      <p class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ entry.project_name }}</p>
                    </div>
                    <span class="font-mono text-xs tabular-nums flex-shrink-0"
                          :class="entry.status === 'running' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'">
                      {{ entry.elapsed_formatted }}
                    </span>
                    <div class="flex items-center gap-1 flex-shrink-0">
                      <button v-if="entry.status === 'running'" @click.stop="pauseTimer(entry)" :disabled="entry.loading"
                              class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition-colors disabled:opacity-40" title="Pause">
                        <i class="bi bi-pause-fill text-xs"></i>
                      </button>
                      <button v-if="entry.status === 'paused'" @click.stop="resumeTimer(entry)" :disabled="entry.loading"
                              class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors disabled:opacity-40" title="Resume">
                        <i class="bi bi-play-fill text-xs"></i>
                      </button>
                      <button @click.stop="stopTimer(entry)" :disabled="entry.loading"
                              class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors disabled:opacity-40" title="Stop">
                        <i class="bi bi-stop-fill text-xs"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div v-if="timerHasActive" class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                  <Link :href="route('time-entries.index')" class="text-xs text-violet-600 dark:text-violet-400 hover:underline" @click="timerOpen = false">
                    View all time entries
                  </Link>
                  <button @click="stopAllTimers" class="text-xs text-red-500 hover:text-red-600 dark:hover:text-red-400 hover:underline">
                    Stop all
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Chat icon with unread badge -->
          <Link :href="route('chat.index')"
                class="btn-icon btn-ghost relative"
                title="Chat"
                :class="isActive('chat.*') ? 'bg-slate-100 dark:bg-slate-800' : ''">
            <i class="bi bi-chat-dots text-[15px] text-slate-500 dark:text-slate-400"></i>
            <span v-if="unreadChatCount > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-violet-600 text-white
                         text-[9px] font-bold rounded-full flex items-center justify-center px-1
                         ring-2 ring-white dark:ring-slate-950">
              {{ unreadChatCount > 9 ? '9+' : unreadChatCount }}
            </span>
          </Link>

          <!-- Notifications -->
          <div ref="notifContainerRef" class="relative">
            <button @click.stop="toggleNotif"
                    class="btn-icon btn-ghost relative"
                    :class="notifOpen ? 'bg-slate-100 dark:bg-slate-800' : ''">
              <i class="bi bi-bell text-[15px] text-slate-500 dark:text-slate-400"></i>
              <span v-if="notifCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-rose-500 text-white
                           text-[9px] font-bold rounded-full flex items-center justify-center px-1
                           ring-2 ring-white dark:ring-slate-950">
                {{ notifCount > 9 ? '9+' : notifCount }}
              </span>
            </button>
            <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
              <div v-if="notifOpen" class="dropdown-menu absolute right-0 top-full mt-2 w-80">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">
                    Notifications
                    <span v-if="notifCount > 0" class="ml-1.5 text-xs font-normal text-violet-500">{{ notifCount }} unread</span>
                  </span>
                  <div class="flex items-center gap-2">
                    <button v-if="notifCount > 0" @click="markAllNotifsRead" class="text-xs text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">Mark all read</button>
                    <Link :href="route('notifications.index')" class="text-xs text-violet-600 hover:text-violet-700 font-medium" @click="notifOpen = false">View all</Link>
                  </div>
                </div>
                <div class="max-h-80 overflow-y-auto">
                  <div v-if="!notifItems.length" class="py-10 text-center">
                    <i class="bi bi-bell-slash text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                    <p class="text-xs text-slate-400">All caught up!</p>
                  </div>
                  <a v-for="n in notifItems.slice(0,6)" :key="n.id"
                     :href="n.data?.url || '#'"
                     @click.prevent="markNotifRead(n)"
                     class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors border-b border-slate-50 dark:border-slate-800/60 last:border-b-0 cursor-pointer">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                         :class="{
                           'bg-violet-100 dark:bg-violet-900/30': n.data?.type === 'task_assigned',
                           'bg-sky-100 dark:bg-sky-900/30': n.data?.type === 'task_commented',
                           'bg-emerald-100 dark:bg-emerald-900/30': n.data?.type === 'task_status_changed',
                           'bg-amber-100 dark:bg-amber-900/30': n.data?.type === 'project_invite',
                           'bg-slate-100 dark:bg-slate-800': !n.data?.type,
                         }">
                      <i class="text-xs"
                         :class="{
                           'bi bi-person-plus text-violet-500': n.data?.type === 'task_assigned',
                           'bi bi-chat-left-text text-sky-500': n.data?.type === 'task_commented',
                           'bi bi-arrow-right-circle text-emerald-500': n.data?.type === 'task_status_changed',
                           'bi bi-folder-plus text-amber-500': n.data?.type === 'project_invite',
                           'bi bi-bell text-slate-400': !n.data?.type,
                         }"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs leading-snug"
                         :class="n.read_at ? 'text-slate-500 dark:text-slate-400' : 'text-slate-800 dark:text-white font-medium'">
                        {{ n.data?.message || 'New notification' }}
                      </p>
                      <p class="text-[10px] text-slate-400 mt-1">{{ timeAgo(n.created_at) }}</p>
                    </div>
                    <div v-if="!n.read_at" class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0 mt-1.5"></div>
                  </a>
                </div>
              </div>
            </Transition>
          </div>

          <!-- User menu -->
          <div ref="userMenuContainerRef" class="relative">
            <button @click.stop="userMenuOpen = !userMenuOpen"
                    class="flex items-center gap-2 pl-2 pr-2.5 py-1.5 rounded-xl
                           hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-150"
                    :class="userMenuOpen ? 'bg-slate-100 dark:bg-slate-800' : ''">
              <img v-if="authUser?.avatar_url" :src="authUser.avatar_url"
                   class="w-7 h-7 rounded-full ring-2 ring-slate-200 dark:ring-slate-700 flex-shrink-0">
              <div v-else
                   class="w-7 h-7 rounded-full ring-2 ring-slate-200 dark:ring-slate-700
                          bg-gradient-to-br from-violet-500 to-indigo-600
                          flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0">
                {{ userInitials }}
              </div>
              <div class="hidden md:block text-left">
                <p class="text-xs font-semibold text-slate-900 dark:text-white leading-none">{{ authUser?.name }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5 capitalize leading-none">{{ authUser?.roles?.[0] ?? 'member' }}</p>
              </div>
              <i class="bi bi-chevron-down text-[9px] text-slate-400 hidden md:block"></i>
            </button>
            <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
              <div v-if="userMenuOpen" class="dropdown-menu absolute right-0 top-full mt-2 w-52">
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                  <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ authUser?.name }}</p>
                  <p class="text-xs text-slate-400 truncate">{{ authUser?.email }}</p>
                </div>
                <div class="py-1">
                  <Link :href="route('settings.profile')" class="dropdown-item text-sm" @click="userMenuOpen = false">
                    <i class="bi bi-person-circle text-slate-400 text-sm"></i> Profile settings
                  </Link>
                  <Link :href="route('daily-reports.create')" class="dropdown-item text-sm" @click="userMenuOpen = false">
                    <i class="bi bi-journal-plus text-slate-400 text-sm"></i> Submit daily report
                  </Link>
                  <Link :href="route('attendance.index')" class="dropdown-item text-sm" @click="userMenuOpen = false">
                    <i class="bi bi-clock text-slate-400 text-sm"></i> My attendance
                  </Link>
                </div>
                <div class="dropdown-divider"></div>
                <form method="POST" :action="route('logout')" @submit.prevent="logout">
                  <button type="submit" class="dropdown-item w-full text-rose-500 dark:text-rose-400 text-sm">
                    <i class="bi bi-box-arrow-right text-sm"></i> Sign out
                  </button>
                </form>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <!-- Flash messages -->
      <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
        <div v-if="flashSuccess" class="mx-4 lg:mx-5 mt-4 alert-success">
          <i class="bi bi-check-circle-fill flex-shrink-0"></i>
          <span class="flex-1">{{ flashSuccess }}</span>
          <button @click="flashSuccess = null" class="text-emerald-600/60 hover:text-emerald-800 dark:hover:text-emerald-200">
            <i class="bi bi-x-lg text-sm"></i>
          </button>
        </div>
      </Transition>
      <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
        <div v-if="flashError" class="mx-4 lg:mx-5 mt-4 alert-danger">
          <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
          <span class="flex-1">{{ flashError }}</span>
          <button @click="flashError = null" class="text-rose-600/60 hover:text-rose-800">
            <i class="bi bi-x-lg text-sm"></i>
          </button>
        </div>
      </Transition>
      <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
        <div v-if="flashInfo" class="mx-4 lg:mx-5 mt-4 alert-info">
          <i class="bi bi-info-circle-fill flex-shrink-0"></i>
          <span class="flex-1">{{ flashInfo }}</span>
        </div>
      </Transition>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <div class="px-4 lg:px-5 py-5 max-w-screen-2xl mx-auto animate-fade-in">
          <slot />
        </div>
      </main>
    </div>
  </div>

  <!-- QUICK-ADD TASK MODAL (⌘K) -->
  <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
              leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="quickAdd" class="quick-add-modal">
      <div class="quick-add-backdrop" @click="quickAdd = false"></div>
      <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-2" enter-to-class="opacity-100 scale-100 translate-y-0">
        <div v-if="quickAdd" class="quick-add-panel">
          <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
              <i class="bi bi-plus-lg text-violet-600 dark:text-violet-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold text-slate-900 dark:text-white">Quick Add Task</span>
            <kbd class="ml-auto text-[10px] font-mono text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">Esc</kbd>
          </div>
          <form @submit.prevent="submitQuickAdd" class="p-4 space-y-3">
            <input type="text" v-model="quickAddTitle" ref="quickAddInput"
                   placeholder="Task title…" required
                   class="form-input text-base font-medium placeholder-slate-300 dark:placeholder-slate-600">
            <div class="flex items-center gap-1.5 flex-wrap">
              <button v-for="tv in taskTypes" :key="tv.value" type="button"
                      class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-medium cursor-pointer transition-all duration-100
                             border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400
                             hover:border-violet-400 hover:text-violet-600"
                      :class="quickAddType === tv.value ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' : ''"
                      @mousedown.prevent
                      @click="quickAddType = tv.value">
                <i class="bi" :class="[tv.icon, tv.color]"></i> {{ tv.label }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Project</label>
                <ProjectSelect v-model="quickAddProject" :projects="quickAddProjects" placeholder="Select project…" size="sm" />
              </div>
              <div>
                <label class="form-label">Priority</label>
                <select v-model="quickAddPriority" name="priority" class="form-input text-sm">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="critical">Critical</option>
                </select>
              </div>
            </div>
            <div class="flex items-center gap-2 pt-1">
              <button type="submit" class="btn-primary btn-sm flex-1 justify-center">
                <i class="bi bi-check-lg"></i> Add Task
              </button>
              <button type="button" @click="quickAdd = false" class="btn-secondary btn-sm px-4">Cancel</button>
            </div>
          </form>
        </div>
      </Transition>
    </div>
  </Transition>

  <!-- Toasts -->
  <div class="fixed bottom-5 right-5 z-[200] flex flex-col gap-2 pointer-events-none">
    <TransitionGroup
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-x-4"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-4"
    >
      <div v-for="t in toasts" :key="t.id"
           class="pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-sm font-medium max-w-sm
                  bg-slate-900 dark:bg-white text-white dark:text-slate-900 border border-white/10 dark:border-slate-200">
        <i class="bi flex-shrink-0"
           :class="t.type === 'error' ? 'bi-exclamation-circle-fill text-rose-400' : t.type === 'warning' ? 'bi-exclamation-triangle-fill text-amber-400' : 'bi-check-circle-fill text-emerald-400'"></i>
        <span>{{ t.message }}</span>
      </div>
    </TransitionGroup>
  </div>

  <!-- Overtime alert modal -->
  <Teleport to="body">
    <Transition name="ot-fade">
      <div v-if="overtimeAlertOpen"
           class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
           style="background:rgba(15,23,42,.7);backdrop-filter:blur(4px)">
        <div class="w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl"
             style="background:#1e293b;border:1.5px solid #f59e0b">
          <div class="px-6 pt-6 pb-4 text-center">
            <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center"
                 style="background:rgba(245,158,11,.15)">
              <i class="bi bi-clock-history text-2xl" style="color:#f59e0b"></i>
            </div>
            <h2 class="text-lg font-bold text-white mb-1">Still working?</h2>
            <p class="text-sm text-slate-400">
              You've been working for <span class="font-semibold text-white">{{ presenceElapsed }}</span>,
              which exceeds your scheduled {{ workingHoursPerDay }}h day.
            </p>
            <p class="text-xs text-amber-400 mt-3 font-medium">
              Auto clock-out in <span class="font-mono">{{ fmtCountdown(overtimeAutooutSecs) }}</span>
            </p>
          </div>
          <div class="flex gap-3 px-6 pb-6">
            <button @click="dismissOvertimeAlert"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                    style="background:#334155;color:#e2e8f0">
              <i class="bi bi-play-fill mr-1"></i> Still working
            </button>
            <button @click="() => { overtimeAlertOpen = false; clearInterval(overtimeCountdownTimer); sendPresence('/attendance/clock-out', 'post') }"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                    style="background:#f59e0b;color:#1e293b">
              <i class="bi bi-box-arrow-right mr-1"></i> Clock out
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ProjectSelect from '@/Components/ProjectSelect.vue';
import { useClickOutside } from '@/Composables/useClickOutside';

const page = usePage();

const authUser    = computed(() => page.props.auth?.user);
const navBadges   = computed(() => page.props.navBadges ?? {});
const unreadChatCount = computed(() => page.props.unreadChatCount ?? 0);

// ── Dark mode (persisted in localStorage) ──────────────────────
const darkMode = ref(
  localStorage.getItem('darkMode') === 'true' || false
);
function toggleDark() {
  darkMode.value = !darkMode.value;
  localStorage.setItem('darkMode', String(darkMode.value));
  document.documentElement.classList.toggle('dark', darkMode.value);
}
onMounted(() => {
  document.documentElement.classList.toggle('dark', darkMode.value);
});

// ── Sidebar ────────────────────────────────────────────────────
const sidebarOpen = ref(localStorage.getItem('sidebarOpen') !== 'false');
watch(sidebarOpen, (v) => localStorage.setItem('sidebarOpen', String(v)));
const mobileSidebar = ref(false);

// ── Quick Add ──────────────────────────────────────────────────
const quickAdd       = ref(false);
const quickAddTitle  = ref('');
const quickAddProject = ref('');
const quickAddPriority = ref('medium');
const quickAddType   = ref('task');
const quickAddInput  = ref(null);
const quickAddProjects = ref([]);

const taskTypes = [
  { value: 'task',    icon: 'bi-check-square-fill', color: 'text-slate-500',  label: 'Task' },
  { value: 'bug',     icon: 'bi-bug-fill',           color: 'text-red-500',    label: 'Bug' },
  { value: 'feature', icon: 'bi-stars',              color: 'text-purple-500', label: 'Feature' },
  { value: 'story',   icon: 'bi-bookmark-fill',      color: 'text-blue-500',   label: 'Story' },
  { value: 'epic',    icon: 'bi-lightning-fill',      color: 'text-orange-500', label: 'Epic' },
];

watch(quickAdd, (v) => {
  if (v) {
    nextTick(() => quickAddInput.value?.focus());
    fetchQuickAddProjects();
  }
});

async function fetchQuickAddProjects() {
  try {
    const r = await window.axios.get('/projects/list', { params: { status: 'active' }, headers: { Accept: 'application/json' } });
    quickAddProjects.value = r.data;
  } catch (_) {}
}

async function submitQuickAdd() {
  try {
    await window.axios.post(route('tasks.quickStore'), {
      title: quickAddTitle.value,
      type: quickAddType.value,
      project_id: quickAddProject.value,
      priority: quickAddPriority.value,
    });
    quickAdd.value = false;
    quickAddTitle.value = '';
    quickAddProject.value = '';
    quickAddType.value = 'task';
    quickAddPriority.value = 'medium';
    addToast('Task created!', 'success');
  } catch (_) {
    addToast('Failed to create task', 'error');
  }
}

// ── Active route detection ─────────────────────────────────────
function isActive(pattern) {
  const url = page.url;
  const name = page.component;
  if (!pattern) return false;
  if (pattern.endsWith('.*')) {
    const prefix = pattern.slice(0, -2);
    return name.toLowerCase().startsWith(prefix.toLowerCase().replace(/\./g, '/').replace(/-/g, ''));
  }
  return name.toLowerCase() === pattern.toLowerCase().replace(/\./g, '/').replace(/-/g, '');
}

// ── Nav groups ─────────────────────────────────────────────────
const navGroups = computed(() => {
  const user = authUser.value;
  const isAdmin = user?.roles?.includes('admin');
  return [
    {
      name: 'MAIN',
      color: 'text-slate-400 dark:text-slate-300/70',
      items: [
        { route: 'dashboard',     icon: 'bi-house-fill',       label: 'Home',         match: 'dashboard' },
        { route: 'my-tasks',      icon: 'bi-check2-square',    label: 'Task List',    match: 'my-tasks' },
        { route: 'projects.index',icon: 'bi-folder2-open',     label: 'Projects',     match: 'projects.*' },
      ],
    },
    {
      name: 'WORK',
      color: 'text-sky-500 dark:text-sky-300/70',
      items: [
        { route: 'standups.index',      icon: 'bi-mic',            label: 'Standups',      match: 'standups.*' },
        { route: 'bugs.index',          icon: 'bi-bug',            label: 'Bug Tracker',   match: 'bugs.*' },
        { route: 'daily-reports.index', icon: 'bi-journal-text',   label: 'Daily Reports', match: 'daily-reports.*' },
        { route: 'time-entries.index',  icon: 'bi-stopwatch',      label: 'Time Tracker',  match: 'time-entries.*' },
        { route: 'reports.index',       icon: 'bi-bar-chart-line', label: 'Reports',       match: 'reports.*' },
      ],
    },
    {
      name: 'PEOPLE',
      color: 'text-emerald-500 dark:text-emerald-300/70',
      items: [
        { route: 'people.index',        icon: 'bi-people',         label: 'Directory',     match: 'people.*' },
        { route: 'teams.index',         icon: 'bi-diagram-3',      label: 'Teams',         match: 'teams.*' },
        { route: 'expenses.index',      icon: 'bi-receipt',        label: 'Expenses',      match: 'expenses.*', badge: 'expenses' },
        { route: 'leave.index',         icon: 'bi-calendar-minus', label: 'Leave',         match: 'leave.*', badge: 'leave' },
        { route: 'attendance.index',    icon: 'bi-calendar-check', label: 'Attendance',    match: 'attendance.*' },
        { route: 'announcements.index', icon: 'bi-megaphone',      label: 'Announcements', match: 'announcements.*' },
      ],
    },
  ];
});

const adminLinks = computed(() => {
  const user = authUser.value;
  if (!user) return [];
  const isAdmin = user.roles?.includes('admin');
  const perms = user.permissions ?? [];
  const can = (p) => isAdmin || perms.includes(p);
  return [
    { route: 'admin.dashboard',        match: 'admin.dashboard',    icon: 'bi-shield-check',  label: 'Admin Overview',   show: isAdmin },
    { route: 'users.index',            match: 'users.*',            icon: 'bi-person-gear',   label: 'Members',          show: isAdmin || can('users.manage') },
    { route: 'admin.roles.index',      match: 'admin.roles.*',      icon: 'bi-key',           label: 'Roles & Perms',    show: isAdmin || can('roles.manage') },
    { route: 'admin.attendance.index', match: 'admin.attendance.*', icon: 'bi-person-check',  label: 'Team Attendance',  show: isAdmin || can('attendance.view') || can('attendance.manage') },
    { route: 'admin.leave.index',      match: 'admin.leave.*',      icon: 'bi-calendar-range',label: 'Leave & Holidays', show: isAdmin || can('leave.manage') },
    { route: 'admin.activity.index',   match: 'admin.activity.*',   icon: 'bi-activity',      label: 'Activity Log',     show: isAdmin || can('activity.view') },
  ].filter(l => l.show);
});

// ── User menu ──────────────────────────────────────────────────
const userMenuOpen = ref(false);

// ── Dropdown container refs (click-outside) ────────────────────
const searchContainerRef   = ref(null);
const presenceContainerRef = ref(null);
const timerContainerRef    = ref(null);
const notifContainerRef    = ref(null);
const userMenuContainerRef = ref(null);

useClickOutside([
    { elRef: searchContainerRef,   close: () => { searchOpen.value   = false } },
    { elRef: presenceContainerRef, close: () => { presenceOpen.value = false } },
    { elRef: timerContainerRef,    close: () => { timerOpen.value    = false } },
    { elRef: notifContainerRef,    close: () => { notifOpen.value    = false } },
    { elRef: userMenuContainerRef, close: () => { userMenuOpen.value = false } },
]);
const userInitials = computed(() => {
  const name = authUser.value?.name ?? '';
  const parts = name.trim().split(' ');
  return (parts[0]?.[0] ?? '').toUpperCase() + (parts[1]?.[0] ?? '').toUpperCase();
});

function logout() {
  router.post(route('logout'));
}

// ── Flash messages ─────────────────────────────────────────────
const flashSuccess = ref(page.props.flash?.success ?? null);
const flashError   = ref(page.props.flash?.error   ?? null);
const flashInfo    = ref(page.props.flash?.info    ?? null);

router.on('navigate', () => {
  flashSuccess.value = page.props.flash?.success ?? null;
  flashError.value   = page.props.flash?.error   ?? null;
  flashInfo.value    = page.props.flash?.info    ?? null;
  if (flashSuccess.value) setTimeout(() => { flashSuccess.value = null; }, 4000);
  if (flashError.value)   setTimeout(() => { flashError.value   = null; }, 5000);
  if (flashInfo.value)    setTimeout(() => { flashInfo.value    = null; }, 4000);
});

// ── Global search ──────────────────────────────────────────────
const searchQ      = ref('');
const searchOpen   = ref(false);
const searchLoading = ref(false);
const searchGroups = ref([]);
const searchActive = ref(-1);
const searchInput  = ref(null);
let searchTimer    = null;
let searchSeq      = 0;

const searchFlat = computed(() => searchGroups.value.flatMap(g => g.items));

function onSearchInput() {
  clearTimeout(searchTimer);
  if (searchQ.value.trim().length < 2) { searchGroups.value = []; searchOpen.value = false; return; }
  searchTimer = setTimeout(() => fetchSearch(), 200);
}

async function fetchSearch() {
  const seq = ++searchSeq;
  searchLoading.value = true;
  try {
    const r = await window.axios.get(route('search'), { params: { q: searchQ.value }, headers: { Accept: 'application/json' } });
    if (seq !== searchSeq) return;
    searchGroups.value = [
      { label: 'Tasks',    items: r.data.tasks },
      { label: 'Projects', items: r.data.projects },
      { label: 'People',   items: r.data.people },
    ].filter(g => g.items?.length);
    searchActive.value = -1;
    searchOpen.value = true;
  } finally {
    if (seq === searchSeq) searchLoading.value = false;
  }
}

function moveSearch(step) {
  if (!searchFlat.value.length) return;
  searchActive.value = (searchActive.value + step + searchFlat.value.length) % searchFlat.value.length;
}

function goSearch() {
  const item = searchFlat.value[searchActive.value];
  window.location.href = item ? item.url : route('search') + '?q=' + encodeURIComponent(searchQ.value);
}

// ── Notifications ──────────────────────────────────────────────
const notifOpen  = ref(false);
const notifCount = ref(0);
const notifItems = ref([]);
let notifPoll    = null;

function toggleNotif() {
  notifOpen.value = !notifOpen.value;
  if (notifOpen.value) fetchNotifs();
}

async function fetchNotifs() {
  try {
    const r = await window.axios.get(route('notifications.index'), { headers: { Accept: 'application/json' } });
    const newCount = r.data.unread_count ?? 0;
    notifCount.value = newCount;
    notifItems.value = r.data.items ?? [];
  } catch (_) {}
}

async function markNotifRead(n) {
  if (!n.read_at) {
    try {
      await window.axios.patch(`/notifications/${n.id}/read`);
      n.read_at = new Date().toISOString();
      notifCount.value = Math.max(0, notifCount.value - 1);
    } catch (_) {}
  }
  if (n.data?.url) window.location.href = n.data.url;
}

async function markAllNotifsRead() {
  try {
    await window.axios.post('/notifications/read-all');
    notifItems.value.forEach(n => n.read_at = new Date().toISOString());
    notifCount.value = 0;
  } catch (_) {}
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
}

// ── Presence widget ────────────────────────────────────────────
const presenceOpen         = ref(false);
const presenceBusy         = ref(false);
const presenceAvailability = ref(page.props.presence?.availability ?? 'available');
const presenceStatuses     = computed(() => page.props.presence?.statuses ?? {});
const presenceClockedIn    = ref(page.props.presence?.clockedIn ?? false);
const presenceClockInAt     = ref(page.props.presence?.clockInAt ? new Date(page.props.presence.clockInAt) : null);
const presenceWorkedMinutes = ref(page.props.presence?.workedMinutes ?? 0);
const presenceElapsed       = ref('0h 00m');
const workingHoursPerDay    = ref(page.props.presence?.workingHoursPerDay ?? 8);

// ── Overtime alert ─────────────────────────────────────────────
const overtimeAlertOpen     = ref(false);
const overtimeAutooutSecs   = ref(600); // 10-minute countdown
let   overtimeAlertTimer    = null;
let   overtimeCountdownTimer = null;

function totalWorkedMins() {
  let mins = presenceWorkedMinutes.value;
  if (presenceClockedIn.value && presenceClockInAt.value) {
    mins += Math.max(0, Math.floor((Date.now() - presenceClockInAt.value) / 60000));
  }
  return mins;
}

function playAlertSound() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    [[440,0,0.15],[550,0.18,0.15],[440,0.36,0.15],[550,0.54,0.2]].forEach(([freq,start,dur]) => {
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine'; o.frequency.value = freq;
      g.gain.setValueAtTime(0, ctx.currentTime + start);
      g.gain.linearRampToValueAtTime(0.22, ctx.currentTime + start + 0.03);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur);
      o.start(ctx.currentTime + start); o.stop(ctx.currentTime + start + dur + 0.05);
    });
  } catch(_) {}
}

function startOvertimeCountdown() {
  overtimeAutooutSecs.value = 600;
  clearInterval(overtimeCountdownTimer);
  overtimeCountdownTimer = setInterval(() => {
    overtimeAutooutSecs.value--;
    if (overtimeAutooutSecs.value <= 0) {
      clearInterval(overtimeCountdownTimer);
      overtimeAlertOpen.value = false;
      sendPresence('/attendance/clock-out', 'post');
    }
  }, 1000);
}

function dismissOvertimeAlert() {
  overtimeAlertOpen.value = false;
  clearInterval(overtimeCountdownTimer);
  // Suppress for 2 hours
  overtimeAlertTimer = setTimeout(checkOvertime, 2 * 60 * 60 * 1000);
}

function checkOvertime() {
  if (!presenceClockedIn.value) return;
  const worked = totalWorkedMins();
  const limit  = workingHoursPerDay.value * 60;
  if (worked >= limit + 30) { // alert 30 min after expected end
    overtimeAlertOpen.value = true;
    playAlertSound();
    startOvertimeCountdown();
  } else {
    // Check again in 5 minutes
    overtimeAlertTimer = setTimeout(checkOvertime, 5 * 60 * 1000);
  }
}

function fmtCountdown(secs) {
  return `${Math.floor(secs / 60)}:${String(secs % 60).padStart(2, '0')}`;
}
let presenceTick           = null;

const currentPresence = computed(() => presenceStatuses.value[presenceAvailability.value] ?? { dot: 'bg-slate-400', label: 'Available' });

function formatClockIn(d) {
  return d instanceof Date ? d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true }) : '';
}

function tickPresence() {
  let mins = presenceWorkedMinutes.value;
  if (presenceClockedIn.value && presenceClockInAt.value) {
    mins += Math.max(0, Math.floor((Date.now() - presenceClockInAt.value) / 60000));
  }
  presenceElapsed.value = `${Math.floor(mins / 60)}h ${String(mins % 60).padStart(2, '0')}m`;
}

function togglePresence() {
  presenceOpen.value = !presenceOpen.value;
}

async function sendPresence(url, method, body = {}) {
  presenceBusy.value = true;
  try {
    const r = await window.axios({ url, method, data: body });
    presenceAvailability.value = r.data.availability;
    if (r.data.clocked_in !== presenceClockedIn.value) {
      if (!r.data.clocked_in && presenceClockInAt.value) {
        presenceWorkedMinutes.value += Math.max(0, Math.floor((Date.now() - presenceClockInAt.value) / 60000));
      }
      presenceClockedIn.value = r.data.clocked_in;
      presenceClockInAt.value = r.data.clock_in_at ? new Date(r.data.clock_in_at) : null;
    }
    tickPresence();
    addToast(r.data.message, 'success');
  } catch (_) {
    addToast('Could not update your status', 'error');
  } finally {
    presenceBusy.value = false;
  }
}

function setPresenceStatus(key) {
  presenceOpen.value = false;
  sendPresence('/me/availability', 'patch', { availability: key });
}

function toggleClock() {
  presenceOpen.value = false;
  sendPresence(presenceClockedIn.value ? '/attendance/clock-out' : '/attendance/clock-in', 'post');
}

// ── Multi-timer widget ─────────────────────────────────────────
const timerOpen         = ref(false);
const timerEntries      = ref([]);
let timerTick           = null;
let timerPoll           = null;

const timerRunningCount = computed(() => timerEntries.value.filter(e => e.status === 'running').length);
const timerPausedCount  = computed(() => timerEntries.value.filter(e => e.status === 'paused').length);
const timerHasActive    = computed(() => timerEntries.value.length > 0);

function fmtTimer(s) {
  s = Math.max(0, Math.floor(s || 0));
  return [Math.floor(s / 3600), Math.floor((s % 3600) / 60), s % 60]
    .map(n => String(n).padStart(2, '0')).join(':');
}

async function refreshTimers() {
  try {
    const r = await window.axios.get('/time-entries/active', { headers: { Accept: 'application/json' } });
    timerEntries.value = (r.data.entries || []).map(e => ({
      ...e,
      elapsed_formatted: fmtTimer(e.elapsed_seconds),
      loading: false,
    }));
  } catch (_) {}
}

function tickTimers() {
  timerEntries.value = timerEntries.value.map(e => {
    if (e.status !== 'running') return e;
    const start   = Math.floor(new Date(e.started_at).getTime() / 1000);
    const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - start - (e.paused_seconds || 0));
    return { ...e, elapsed_seconds: elapsed, elapsed_formatted: fmtTimer(elapsed) };
  });
}

async function pauseTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    const r = await window.axios.patch(`/time-entries/${entry.id}/pause`);
    const idx = timerEntries.value.findIndex(e => e.id === entry.id);
    if (idx >= 0) timerEntries.value[idx] = { ...timerEntries.value[idx], ...r.data.entry, elapsed_formatted: fmtTimer(r.data.entry.elapsed_seconds), loading: false };
    addToast('Timer paused', 'info');
  } catch (_) { entry.loading = false; }
}

async function resumeTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    const r = await window.axios.patch(`/time-entries/${entry.id}/resume`);
    const idx = timerEntries.value.findIndex(e => e.id === entry.id);
    if (idx >= 0) timerEntries.value[idx] = { ...timerEntries.value[idx], ...r.data.entry, elapsed_formatted: fmtTimer(r.data.entry.elapsed_seconds), loading: false };
    addToast('Timer resumed', 'success');
  } catch (_) { entry.loading = false; }
}

async function stopTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    await window.axios.post(`/time-entries/${entry.id}/stop`);
    timerEntries.value = timerEntries.value.filter(e => e.id !== entry.id);
    addToast('Timer stopped', 'success');
  } catch (_) { entry.loading = false; }
}

async function stopAllTimers() {
  for (const e of [...timerEntries.value]) await stopTimer(e);
}

// ── Toasts ─────────────────────────────────────────────────────
const toasts = ref([]);

function addToast(message, type = 'success') {
  const id = Date.now();
  toasts.value.push({ id, message, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

// ── Keyboard shortcuts ──────────────────────────────────────────
function onKeydown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); quickAdd.value = true; }
  if (e.key === 'Escape') { quickAdd.value = false; mobileSidebar.value = false; }
  if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
    e.preventDefault(); searchInput.value?.focus();
  }
}

// ── Lifecycle ──────────────────────────────────────────────────
function sendBeaconClockOut() {
  if (!presenceClockedIn.value) return;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (!csrf) return;
  const data = new FormData();
  data.append('_token', csrf);
  navigator.sendBeacon('/attendance/beacon-clock-out', data);
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown);
  window.addEventListener('beforeunload', sendBeaconClockOut);
  refreshTimers();
  fetchNotifs();
  tickPresence();
  timerTick    = setInterval(tickTimers,   1000);
  timerPoll    = setInterval(refreshTimers, 30000);
  presenceTick = setInterval(tickPresence, 30000);
  notifPoll    = setInterval(fetchNotifs,  60000);
  // Start overtime check after 5 minutes
  overtimeAlertTimer = setTimeout(checkOvertime, 5 * 60 * 1000);
});

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown);
  window.removeEventListener('beforeunload', sendBeaconClockOut);
  clearInterval(timerTick);
  clearInterval(timerPoll);
  clearInterval(presenceTick);
  clearInterval(notifPoll);
  clearTimeout(overtimeAlertTimer);
  clearInterval(overtimeCountdownTimer);
});

// Scroll active nav item into view on page navigation
const navEl = ref(null);
router.on('navigate', () => {
  nextTick(() => {
    const a = navEl.value?.querySelector('.sidebar-nav-active');
    if (a) a.scrollIntoView({ block: 'nearest' });
  });
});
</script>

<style>
.ot-fade-enter-active, .ot-fade-leave-active { transition: opacity .2s; }
.ot-fade-enter-from, .ot-fade-leave-to { opacity: 0; }
</style>
