<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <div class="space-y-3">
      <p class="text-sm font-medium uppercase tracking-[0.2em] text-cyan-300">About</p>
      <h1 class="text-4xl font-semibold tracking-tight text-white">A polished foundation for the dating experience</h1>
      <p class="text-base leading-7 text-slate-300">
        This interface keeps the existing application architecture intact while presenting the UI with modern
        Tailwind utility classes instead of component-scoped custom CSS.
      </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <h2 class="text-lg font-semibold text-white">What stays the same</h2>
        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
          <li>• Vue 3 composition API and <code class="rounded bg-white/10 px-1.5 py-0.5 text-cyan-200">script setup</code></li>
          <li>• Vue Router navigation and route structure</li>
          <li>• Pinia stores and existing API contracts</li>
        </ul>
      </article>

      <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <h2 class="text-lg font-semibold text-white">What changes</h2>
        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
          <li>• Custom CSS is replaced with utility-first styling</li>
          <li>• Layout, spacing, and color treatment become more consistent</li>
          <li>• Components remain easy to reason about and extend</li>
        </ul>
      </article>
    </div>
  </section>
</template>