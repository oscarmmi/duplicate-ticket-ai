<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Ticket = {
    id: number;
    subject: string;
    body: string;
    status: 'open' | 'closed';
    createdAt: string | null;
};

type Match = {
    id: number;
    ticketId: number;
    subject: string;
    body: string;
    status: 'open' | 'closed';
    score: number;
    llmConfirmed: boolean;
    reasons: string[];
};

type Props = {
    tickets: Ticket[];
    stats: { total: number; open: number; checks: number };
    selectedCheck: null | {
        id: number;
        status: string;
        ticketId: number;
        ticketSubject: string;
        matches: Match[];
    };
};

const props = defineProps<Props>();
const search = ref('');
const filter = ref<'all' | 'open' | 'closed'>('all');
const analyzingTicket = ref<number | null>(null);

const filteredTickets = computed(() =>
    props.tickets.filter((ticket) => {
        const matchesFilter =
            filter.value === 'all' || ticket.status === filter.value;
        const query = search.value.trim().toLowerCase();
        const matchesSearch =
            query === '' ||
            ticket.subject.toLowerCase().includes(query) ||
            ticket.body.toLowerCase().includes(query);

        return matchesFilter && matchesSearch;
    }),
);

function findDuplicates(ticketId: number) {
    analyzingTicket.value = ticketId;
    router.post(
        `/tickets/${ticketId}/duplicates`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                analyzingTicket.value = null;
            },
        },
    );
}

function confidenceLabel(score: number) {
    if (score >= 0.55) return 'Strong signal';
    if (score >= 0.38) return 'Good signal';
    return 'Possible match';
}

function percent(score: number) {
    return `${Math.round(score * 100)}%`;
}

function dateLabel(value: string | null) {
    if (!value) return 'Recently updated';

    return new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Duplicate detection" />

    <div class="min-h-screen bg-[#f6f7f9] text-slate-900">
        <aside
            class="fixed inset-y-0 left-0 z-20 hidden w-[248px] flex-col border-r border-slate-200 bg-white lg:flex"
        >
            <div
                class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-6"
            >
                <div
                    class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-200"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        class="size-5"
                        aria-hidden="true"
                    >
                        <path
                            d="M8 7h9a3 3 0 0 1 0 6h-1M16 17H7a3 3 0 0 1 0-6h1m0-4 3-3 3 3m-6 10 3 3 3-3"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
                <div>
                    <p class="text-[15px] font-semibold tracking-tight">
                        Harbor Desk
                    </p>
                    <p class="text-[11px] font-medium text-slate-400">
                        SUPPORT OPERATIONS
                    </p>
                </div>
            </div>

            <div class="px-4 pt-7">
                <p
                    class="mb-3 px-3 text-[10px] font-bold tracking-[0.16em] text-slate-400"
                >
                    WORKSPACE
                </p>
                <a
                    href="#tickets"
                    class="flex items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        class="size-[18px]"
                        aria-hidden="true"
                    >
                        <path
                            d="M5 6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 17.5v-11Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                        <path
                            d="M8 9h8M8 12h8M8 15h5"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                    Tickets
                    <span
                        class="ml-auto rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-indigo-600"
                        >{{ stats.total }}</span
                    >
                </a>
                <div
                    class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        class="size-[18px]"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 5h16M4 12h10M4 19h7m5-9 2 2 4-4"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Duplicate checks
                    <span class="ml-auto text-xs text-slate-400">{{
                        stats.checks
                    }}</span>
                </div>
            </div>

            <div class="mt-auto border-t border-slate-100 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800"
                    >
                        HD
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-semibold text-slate-700"
                        >
                            Harbor Desk team
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            Demo workspace
                        </p>
                    </div>
                    <span
                        class="size-2 rounded-full bg-emerald-500"
                        title="Workspace active"
                    ></span>
                </div>
            </div>
        </aside>

        <main class="lg:pl-[248px]">
            <header
                class="sticky top-0 z-10 flex h-[76px] items-center justify-between border-b border-slate-200/80 bg-white/90 px-5 backdrop-blur-md sm:px-8 xl:px-10"
            >
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span>Support</span><span>/</span
                        ><span class="font-medium text-slate-600">Tickets</span>
                    </div>
                    <h1 class="mt-1 text-[15px] font-semibold tracking-tight">
                        Ticket intelligence
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold text-emerald-700 sm:flex"
                    >
                        <span
                            class="size-1.5 rounded-full bg-emerald-500"
                        ></span>
                        AI engine ready
                    </span>
                    <button
                        class="flex size-9 items-center justify-center rounded-full border border-slate-200 text-slate-500"
                        aria-label="Notifications"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-[17px]"
                            aria-hidden="true"
                        >
                            <path
                                d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                    <div
                        class="flex size-9 items-center justify-center rounded-full bg-slate-800 text-[11px] font-bold text-white"
                    >
                        HD
                    </div>
                </div>
            </header>

            <div
                class="mx-auto max-w-[1440px] px-5 py-7 sm:px-8 sm:py-9 xl:px-10"
            >
                <section
                    class="mb-8 flex flex-col justify-between gap-6 md:flex-row md:items-end"
                >
                    <div class="max-w-2xl">
                        <div
                            class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white px-2.5 py-1 text-[10px] font-bold tracking-[0.12em] text-indigo-600 shadow-sm"
                        >
                            <span
                                class="size-1.5 rounded-full bg-indigo-500"
                            ></span>
                            AI-ASSISTED WORKFLOW
                        </div>
                        <h2
                            class="text-3xl font-semibold tracking-[-0.04em] text-slate-950 sm:text-[34px]"
                        >
                            Find the signal in your queue.
                        </h2>
                        <p
                            class="mt-2 max-w-xl text-sm leading-6 text-slate-500"
                        >
                            Surface recurring customer issues with explainable
                            similarity scores, then let AI confirm the likely
                            duplicates.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3ZM19 16l.9 2.1L22 19l-2.1.9L19 22l-.9-2.1L16 19l2.1-.9L19 16Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <span
                            ><strong class="font-semibold text-slate-700"
                                >TF-IDF</strong
                            >
                            retrieval
                            <span class="mx-1 text-slate-300">·</span> optional
                            Gemini confirmation</span
                        >
                    </div>
                </section>

                <section class="mb-7 grid gap-4 sm:grid-cols-3">
                    <article
                        class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-slate-500">
                                Tickets in corpus
                            </p>
                            <span
                                class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
                                ><svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 17.5v-11Z"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                    />
                                    <path
                                        d="M8 9h8M8 12h8M8 15h5"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                    /></svg
                            ></span>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tracking-tight">
                            {{ stats.total }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Sample tickets in Harbor Desk
                        </p>
                    </article>
                    <article
                        class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-slate-500">
                                Open tickets
                            </p>
                            <span
                                class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
                                ><svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="8.5"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                    />
                                    <path
                                        d="M12 7v5l3 2"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                    /></svg
                            ></span>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tracking-tight">
                            {{ stats.open }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Awaiting a support resolution
                        </p>
                    </article>
                    <article
                        class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-slate-500">
                                Duplicate analyses
                            </p>
                            <span
                                class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700"
                                ><svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 7h9a3 3 0 0 1 0 6h-1M16 17H7a3 3 0 0 1 0-6h1m0-4 3-3 3 3m-6 10 3 3 3-3"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    /></svg
                            ></span>
                        </div>
                        <p class="mt-3 text-2xl font-semibold tracking-tight">
                            {{ stats.checks }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Retrieval runs completed
                        </p>
                    </article>
                </section>

                <section
                    id="tickets"
                    class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)]"
                >
                    <div
                        class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
                    >
                        <div
                            class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <h3 class="text-sm font-semibold">
                                    Support tickets
                                </h3>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Choose a ticket to look for similar issues.
                                </p>
                            </div>
                            <label class="relative block sm:w-[210px]">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="10.8"
                                        cy="10.8"
                                        r="6.3"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                    <path
                                        d="m15.5 15.5 4 4"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <input
                                    v-model="search"
                                    type="search"
                                    placeholder="Search tickets"
                                    class="h-9 w-full rounded-lg border border-slate-200 bg-white pr-3 pl-9 text-xs transition outline-none placeholder:text-slate-400 focus:border-indigo-300 focus:ring-3 focus:ring-indigo-100"
                                />
                            </label>
                        </div>
                        <div
                            class="flex items-center gap-1 border-b border-slate-100 px-5 py-2.5"
                        >
                            <button
                                v-for="tab in [
                                    'all',
                                    'open',
                                    'closed',
                                ] as const"
                                :key="tab"
                                @click="filter = tab"
                                class="rounded-md px-2.5 py-1.5 text-[11px] font-semibold capitalize transition"
                                :class="
                                    filter === tab
                                        ? 'bg-slate-100 text-slate-800'
                                        : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'
                                "
                            >
                                {{ tab
                                }}<span
                                    v-if="tab === 'all'"
                                    class="ml-1 text-slate-400"
                                    >{{ stats.total }}</span
                                >
                            </button>
                            <span class="ml-auto text-[10px] text-slate-400"
                                >{{ filteredTickets.length }} results</span
                            >
                        </div>

                        <div class="divide-y divide-slate-100">
                            <article
                                v-for="ticket in filteredTickets"
                                :key="ticket.id"
                                class="group px-5 py-4 transition hover:bg-slate-50/70"
                                :class="
                                    selectedCheck?.ticketId === ticket.id
                                        ? 'bg-indigo-50/40'
                                        : ''
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-[10px] font-bold text-slate-500"
                                    >
                                        #{{
                                            String(ticket.id).padStart(3, '0')
                                        }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h4
                                                class="text-xs leading-5 font-semibold text-slate-800"
                                            >
                                                {{ ticket.subject }}
                                            </h4>
                                            <span
                                                class="rounded-full px-2 py-0.5 text-[9px] font-semibold capitalize"
                                                :class="
                                                    ticket.status === 'open'
                                                        ? 'bg-amber-50 text-amber-700'
                                                        : 'bg-slate-100 text-slate-500'
                                                "
                                                >{{ ticket.status }}</span
                                            >
                                        </div>
                                        <p
                                            class="mt-1 line-clamp-2 text-[11px] leading-[18px] text-slate-500"
                                        >
                                            {{ ticket.body }}
                                        </p>
                                        <div
                                            class="mt-2.5 flex flex-wrap items-center gap-2 text-[10px] text-slate-400"
                                        >
                                            <span>{{
                                                dateLabel(ticket.createdAt)
                                            }}</span
                                            ><span>·</span
                                            ><span>Harbor Desk</span>
                                        </div>
                                    </div>
                                    <button
                                        @click="findDuplicates(ticket.id)"
                                        :disabled="analyzingTicket !== null"
                                        class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-2.5 text-[10px] font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 disabled:cursor-wait disabled:opacity-60 sm:px-3"
                                    >
                                        <svg
                                            v-if="analyzingTicket === ticket.id"
                                            class="size-3.5 animate-spin"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="9"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                class="opacity-20"
                                            />
                                            <path
                                                d="M21 12a9 9 0 0 0-9-9"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            class="size-3.5"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M8 7h9a3 3 0 0 1 0 6h-1M16 17H7a3 3 0 0 1 0-6h1m0-4 3-3 3 3m-6 10 3 3 3-3"
                                                stroke="currentColor"
                                                stroke-width="1.6"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                        <span class="hidden sm:inline">{{
                                            analyzingTicket === ticket.id
                                                ? 'Checking'
                                                : 'Find duplicates'
                                        }}</span>
                                        <span class="sm:hidden">Find</span>
                                    </button>
                                </div>
                            </article>
                            <div
                                v-if="filteredTickets.length === 0"
                                class="px-5 py-12 text-center"
                            >
                                <p class="text-xs font-medium text-slate-600">
                                    No tickets match this search.
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Try another keyword or status filter.
                                </p>
                            </div>
                        </div>
                        <div
                            class="border-t border-slate-100 bg-slate-50/50 px-5 py-3 text-[10px] text-slate-400"
                        >
                            Showing {{ filteredTickets.length }} of
                            {{ stats.total }} sample tickets
                        </div>
                    </div>

                    <aside
                        class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] xl:sticky xl:top-[100px]"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                        >
                            <div>
                                <h3 class="text-sm font-semibold">
                                    Duplicate candidates
                                </h3>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Ranked by corpus similarity
                                </p>
                            </div>
                            <span
                                v-if="selectedCheck"
                                class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[10px] text-slate-500"
                                >RUN #{{ selectedCheck.id }}</span
                            >
                        </div>

                        <template v-if="selectedCheck">
                            <div
                                class="border-b border-slate-100 bg-slate-50/70 px-5 py-3.5"
                            >
                                <p
                                    class="text-[9px] font-bold tracking-[0.13em] text-slate-400"
                                >
                                    ANALYZED TICKET
                                </p>
                                <p
                                    class="mt-1.5 text-xs leading-5 font-semibold text-slate-800"
                                >
                                    {{ selectedCheck.ticketSubject }}
                                </p>
                            </div>
                            <div
                                v-if="selectedCheck.matches.length > 0"
                                class="space-y-3 p-4"
                            >
                                <article
                                    v-for="(
                                        match, index
                                    ) in selectedCheck.matches"
                                    :key="match.id"
                                    class="rounded-lg border border-slate-200 p-4 transition hover:border-slate-300"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2"
                                        >
                                            <span
                                                class="flex size-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[9px] font-bold text-slate-500"
                                                >{{ index + 1 }}</span
                                            >
                                            <p
                                                class="truncate text-xs font-semibold text-slate-800"
                                            >
                                                {{ match.subject }}
                                            </p>
                                        </div>
                                        <span
                                            class="shrink-0 font-mono text-xs font-bold text-indigo-700"
                                            >{{ percent(match.score) }}</span
                                        >
                                    </div>
                                    <div
                                        class="mt-3 h-1 overflow-hidden rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full bg-indigo-500 transition-all"
                                            :style="{
                                                width: percent(match.score),
                                            }"
                                        ></div>
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[9px] font-medium text-slate-400"
                                            >{{
                                                confidenceLabel(match.score)
                                            }}</span
                                        >
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                            :class="
                                                match.llmConfirmed
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-500'
                                            "
                                            >{{
                                                match.llmConfirmed
                                                    ? 'AI confirmed'
                                                    : 'AI unconfirmed'
                                            }}</span
                                        >
                                    </div>
                                    <p
                                        class="mt-3 line-clamp-3 text-[11px] leading-[18px] text-slate-500"
                                    >
                                        {{ match.body }}
                                    </p>
                                    <div
                                        v-if="match.reasons.length"
                                        class="mt-3 flex flex-wrap gap-1.5"
                                    >
                                        <span
                                            v-for="reason in match.reasons.slice(
                                                0,
                                                3,
                                            )"
                                            :key="reason"
                                            class="rounded bg-indigo-50 px-1.5 py-1 text-[9px] font-medium text-indigo-700"
                                            >{{ reason }}</span
                                        >
                                    </div>
                                </article>
                            </div>
                            <div v-else class="px-6 py-12 text-center">
                                <div
                                    class="mx-auto flex size-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        class="size-5"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="m6 12 4 4 8-8"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                                <p
                                    class="mt-3 text-xs font-semibold text-slate-700"
                                >
                                    No likely duplicates found
                                </p>
                                <p
                                    class="mx-auto mt-1 max-w-[230px] text-[11px] leading-5 text-slate-400"
                                >
                                    No ticket exceeded the 25% similarity
                                    threshold in this corpus.
                                </p>
                            </div>
                        </template>
                        <div v-else class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-6"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 7h9a3 3 0 0 1 0 6h-1M16 17H7a3 3 0 0 1 0-6h1m0-4 3-3 3 3m-6 10 3 3 3-3"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <p
                                class="mt-4 text-xs font-semibold text-slate-700"
                            >
                                Ready when you are
                            </p>
                            <p
                                class="mx-auto mt-1 max-w-[240px] text-[11px] leading-5 text-slate-400"
                            >
                                Run duplicate detection on any ticket to see the
                                closest matches and why they ranked.
                            </p>
                        </div>

                        <div
                            class="border-t border-slate-100 bg-slate-50/60 px-5 py-3.5"
                        >
                            <div
                                class="flex items-start gap-2 text-[10px] leading-[17px] text-slate-400"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="mt-0.5 size-3.5 shrink-0 text-slate-400"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="9"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M12 11v5m0-8h.01"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <span
                                    >Scores come from TF-IDF cosine similarity.
                                    LLM confirmation is optional and never
                                    changes candidate ranking.</span
                                >
                            </div>
                        </div>
                    </aside>
                </section>

                <footer
                    class="mt-8 flex flex-col gap-2 border-t border-slate-200/80 py-5 text-[10px] text-slate-400 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span
                        >Harbor Desk is a sample support workspace. All ticket
                        content is fictional.</span
                    >
                    <span
                        >Explainable retrieval
                        <span class="mx-1.5">·</span> Human-reviewed
                        recommendations</span
                    >
                </footer>
            </div>
        </main>
    </div>
</template>
