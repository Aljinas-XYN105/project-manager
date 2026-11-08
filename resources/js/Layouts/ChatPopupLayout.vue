<template>
    <div class="font-sans antialiased" style="height:100vh;overflow:hidden;">
        <style>
        :root {
            --ci-bg: #f8fafc;
            --ci-header-bg: #ffffff;
            --ci-header-border: #e2e8f0;
            --ci-row-hover: #f1f5f9;
            --ci-row-active: #ede9fe;
            --ci-row-active-border: #7c3aed;
            --ci-name: #1e293b;
            --ci-preview: #64748b;
            --ci-time: #94a3b8;
            --ci-avatar-bg: #ede9fe;
            --ci-avatar-fg: #7c3aed;
            --ci-badge-bg: #7c3aed;
            --ci-badge-fg: #ffffff;
            --ci-search-bg: #f1f5f9;
            --ci-search-border: #e2e8f0;
            --ci-sep: #f1f5f9;
            --ci-modal-bg: #ffffff;
            --ci-chip-bg: #ede9fe;
            --ci-chip-fg: #5b21b6;
            --ci-online: #22c55e;
            --ci-offline: #94a3b8;

            --ct-bg: #f1f5f9;
            --ct-header-bg: #ffffff;
            --ct-header-border: #e2e8f0;
            --ct-bubble-out-bg: #7c3aed;
            --ct-bubble-out-fg: #ffffff;
            --ct-bubble-in-bg: #ffffff;
            --ct-bubble-in-fg: #1e293b;
            --ct-bubble-in-border: #e2e8f0;
            --ct-time: #94a3b8;
            --ct-compose-bg: #ffffff;
            --ct-compose-border: #e2e8f0;
            --ct-input-bg: #f8fafc;
            --ct-input-border: #e2e8f0;
            --ct-action-btn: #64748b;
            --ct-action-hover: #7c3aed;
            --ct-send-bg: #7c3aed;
            --ct-sep-text: #94a3b8;
            --ct-sep-line: #e2e8f0;
            --ct-name: #1e293b;
        }
        .dark {
            --ci-bg: #0f172a;
            --ci-header-bg: #1e293b;
            --ci-header-border: #334155;
            --ci-row-hover: #1e293b;
            --ci-row-active: #2e1065;
            --ci-row-active-border: #7c3aed;
            --ci-name: #f1f5f9;
            --ci-preview: #94a3b8;
            --ci-time: #64748b;
            --ci-avatar-bg: #2e1065;
            --ci-avatar-fg: #a78bfa;
            --ci-badge-bg: #7c3aed;
            --ci-badge-fg: #ffffff;
            --ci-search-bg: #1e293b;
            --ci-search-border: #334155;
            --ci-sep: #1e293b;
            --ci-modal-bg: #1e293b;
            --ci-chip-bg: #2e1065;
            --ci-chip-fg: #c4b5fd;
            --ci-online: #22c55e;
            --ci-offline: #475569;

            --ct-bg: #0f172a;
            --ct-header-bg: #1e293b;
            --ct-header-border: #334155;
            --ct-bubble-out-bg: #6d28d9;
            --ct-bubble-out-fg: #f1f5f9;
            --ct-bubble-in-bg: #1e293b;
            --ct-bubble-in-fg: #e2e8f0;
            --ct-bubble-in-border: #334155;
            --ct-time: #64748b;
            --ct-compose-bg: #1e293b;
            --ct-compose-border: #334155;
            --ct-input-bg: #0f172a;
            --ct-input-border: #334155;
            --ct-send-bg: #7c3aed;
            --ct-sep-text: #64748b;
            --ct-sep-line: #1e293b;
            --ct-name: #f1f5f9;
        }
        html, body { height: 100%; overflow: hidden; }
        </style>
        <slot />
    </div>
</template>
