<template>
  <div class="relative min-h-screen overflow-hidden bg-[#f8fafc]">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -left-32 -top-24 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_30%_30%,rgba(20,184,166,0.35),rgba(20,184,166,0))]"></div>
      <div class="absolute -right-28 top-10 h-[360px] w-[360px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(56,189,248,0.35),rgba(56,189,248,0))]"></div>
      <div class="absolute bottom-[-180px] left-[12%] h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(250,204,21,0.25),rgba(250,204,21,0))]"></div>
      <div class="absolute inset-0 bg-[linear-gradient(rgba(15,23,42,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(15,23,42,0.05)_1px,transparent_1px)] bg-[size:90px_90px] opacity-40"></div>
    </div>

    <div class="relative mx-auto flex min-h-screen max-w-4xl items-center px-6 py-16">
      <div class="grid w-full grid-cols-2 items-center gap-8">

        <div class="flex flex-col gap-5">
          <h1 class="text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl">
            Meet people who match your pace.
          </h1>
          <div class="flex flex-wrap gap-3">
            <RouterLink
              :to="{ name: 'auth', query: { mode: 'register' } }"
              class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(15,23,42,0.25)] transition hover:-translate-y-0.5"
            >Create account</RouterLink>
            <RouterLink
              :to="{ name: 'auth', query: { mode: 'login' } }"
              class="inline-flex items-center justify-center rounded-2xl border border-slate-200/80 bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:border-slate-300"
            >Sign in</RouterLink>
          </div>
        </div>

        <div class="relative h-[420px]">
          <!-- Julia -->
          <div class="absolute left-[2%] top-4 z-10 h-[370px] w-[57%] -rotate-[5deg] overflow-hidden rounded-[22px] border-2 border-white/65 shadow-[0_16px_40px_rgba(15,23,42,0.22)]">
            <img src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=600&q=80" alt="Julia" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
              <div class="flex items-baseline gap-2">
                <p class="text-lg font-semibold">Julia</p>
                <span class="text-sm text-white/75">24</span>
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-[11px] text-white/65">
                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                2 km away
              </div>
            </div>
          </div>

          <!-- Leo (blonde) -->
          <div class="absolute right-[2%] top-11 z-20 h-[380px] w-[57%] rotate-[2.5deg] overflow-hidden rounded-[22px] border-2 border-white/65 shadow-[0_18px_44px_rgba(15,23,42,0.25)]">
            <img
              src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80"
              alt="Leo"
              class="h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
              <div class="flex items-baseline gap-2">
                <p class="text-lg font-semibold">Leo</p>
                <span class="text-sm text-white/75">27</span>
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-[11px] text-white/65">
                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                4 km away
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>