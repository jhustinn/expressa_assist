<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Documentation & Guides">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 dark:bg-teal-500/20 text-teal-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Expressa Assist Documentation
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Panduan resmi, arsitektur sistem, CLI reference, dan dokumentasi API.
                        </p>
                    </div>
                </div>

                <a
                    href="https://hermes-agent.nousresearch.com/docs/"
                    target="_blank"
                    rel="noreferrer"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] inline-flex items-center gap-1.5"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                    <span>Official Docs</span>
                </a>
            </div>

            <!-- Docs Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white mb-2">CLI & Commands</h3>
                    <p class="text-xs text-gray-400 leading-relaxed mb-4">
                        Pelajari perintah hermes chat, setup, model, doctor, gateway run, dan subcommands.
                    </p>
                    <code class="block p-3 rounded-lg bg-gray-50 dark:bg-[#151521] text-xs font-mono text-gray-800 dark:text-gray-200">
                        hermes --help<br>
                        hermes gateway run<br>
                        hermes skills list
                    </code>
                </div>

                <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white mb-2">Skills Development</h3>
                    <p class="text-xs text-gray-400 leading-relaxed mb-4">
                        Format file SKILL.md dengan YAML frontmatter, izin eksekusi perintah, dan tags.
                    </p>
                    <code class="block p-3 rounded-lg bg-gray-50 dark:bg-[#151521] text-xs font-mono text-gray-800 dark:text-gray-200">
                        ---<br>
                        name: custom-tool<br>
                        description: "..."<br>
                        ---
                    </code>
                </div>

                <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800">
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white mb-2">Multi-Agent & A2A</h3>
                    <p class="text-xs text-gray-400 leading-relaxed mb-4">
                        Protokol Agent-to-Agent (A2A) Linux Foundation untuk komunikasi antar-agent otonom.
                    </p>
                    <code class="block p-3 rounded-lg bg-gray-50 dark:bg-[#151521] text-xs font-mono text-gray-800 dark:text-gray-200">
                        /.well-known/agent-card.json
                    </code>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
