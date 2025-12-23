<script>
    import { tick } from "svelte";

    // State for Dynamic Summary
    let totalExpense = 75000;
    let currentBalance = 3425000;

    // Helper to format currency
    const formatRp = (num) => {
        return "Rp" + num.toLocaleString("id-ID");
    };

    // Initial messages
    let messages = [
        {
            type: "user",
            text: "Makan siang 25rb",
            time: "12:30",
        },
        {
            type: "bot",
            lines: ["Dicatat", "Kategori: Makan", "Jumlah: Rp25.000"],
            time: "12:30",
        },
    ];

    const scenarios = [
        {
            user: "Beli kopi 20rb",
            amount: 20000,
            isExpense: true,
            bot: {
                lines: ["Dicatat", "Kategori: Jajan", "Jumlah: Rp20.000"],
            },
        },
        {
            user: "Gaji masuk 10jt",
            amount: 10000000,
            isIncome: true,
            bot: {
                lines: ["Pemasukan dicatat", "Rp10.000.000"],
                isIncome: true,
            },
        },
        {
            user: "Bayar listrik 500rb",
            amount: 500000,
            isExpense: true,
            bot: {
                lines: ["Dicatat", "Kategori: Tagihan", "Jumlah: Rp500.000"],
            },
        },
        {
            user: "Service motor 150rb",
            amount: 150000,
            isExpense: true,
            bot: {
                lines: ["Dicatat", "Kategori: Transport", "Jumlah: Rp150.000"],
            },
        },
        {
            user: "Jual kue 250rb",
            amount: 250000,
            isIncome: true,
            bot: {
                lines: ["Pemasukan dicatat", "Rp250.000"],
                isIncome: true,
            },
        },
    ];

    let inputValue = "";
    let isTyping = false;
    let chatContainer;

    export const playDemo = async () => {
        if (isTyping) return;
        isTyping = true;

        // Pick random scenario
        const randomScenario =
            scenarios[Math.floor(Math.random() * scenarios.length)];

        // 1. Type command
        const textToType = randomScenario.user;
        for (let i = 0; i < textToType.length; i++) {
            inputValue += textToType[i];
            await new Promise((r) => setTimeout(r, 40));
        }

        await new Promise((r) => setTimeout(r, 300));

        // 2. User Send
        const now = new Date();
        const timeStr = `${now.getHours()}:${String(now.getMinutes()).padStart(2, "0")}`;

        messages = [
            ...messages,
            {
                type: "user",
                text: inputValue,
                time: timeStr,
            },
        ];
        inputValue = "";
        await scrollToBottom();

        // 3. Bot Processing
        await new Promise((r) => setTimeout(r, 600));

        // 4. Update Balance/Expense Dynamically
        if (randomScenario.isIncome) {
            currentBalance += randomScenario.amount;
        } else {
            totalExpense += randomScenario.amount;
            currentBalance -= randomScenario.amount;
        }

        // 5. Bot Reply
        messages = [
            ...messages,
            {
                type: "bot",
                lines: randomScenario.bot.lines,
                isIncome: randomScenario.bot.isIncome,
                time: timeStr,
            },
        ];
        await scrollToBottom();
        isTyping = false;
    };

    const scrollToBottom = async () => {
        await tick();
        if (chatContainer) {
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: "smooth",
            });
        }
    };
</script>

<div class="iphone-container">
    <div class="iphone">
        <div class="buttons side-button"></div>
        <div class="buttons volume-up"></div>
        <div class="buttons volume-down"></div>

        <div class="bezel">
            <div class="screen">
                <!-- Status Bar -->
                <div class="status-bar">
                    <span class="time">09:41</span>
                    <div class="notch">
                        <div class="camera"></div>
                        <div class="speaker"></div>
                    </div>
                    <div class="icons">
                        <svg viewBox="0 0 24 24" class="icon"
                            ><path
                                fill="currentColor"
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"
                            /></svg
                        >
                        <svg viewBox="0 0 24 24" class="icon"
                            ><path
                                fill="currentColor"
                                d="M15.67 4H14V2h-4v2H8.33C7.6 4 7 4.6 7 5.33v15.33C7 21.4 7.6 22 8.33 22h7.33c.74 0 1.34-.6 1.34-1.33V5.33C17 4.6 16.4 4 15.67 4z"
                            /></svg
                        >
                    </div>
                </div>

                <!-- App Header -->
                <div class="app-header">
                    <div class="back-arrow">←</div>
                    <div class="profile-info">
                        <div class="avatar">
                            <span class="avatar-text">CB</span>
                        </div>
                        <div class="info-text">
                            <div class="name">
                                CatatBot <span class="badge">PRO</span>
                            </div>
                            <div class="status">Online</div>
                        </div>
                    </div>
                    <div class="actions">⋮</div>
                </div>

                <!-- Chat Area -->
                <div class="chat-area" bind:this={chatContainer}>
                    <div class="date-divider">Hari Ini</div>

                    {#each messages as msg}
                        <div class="message {msg.type}">
                            <div class="bubble">
                                {#if msg.type === "bot"}
                                    {#each msg.lines as line, i}
                                        {#if i === 0}
                                            <p><strong>{line}</strong></p>
                                        {:else if i === msg.lines.length - 1 && msg.lines.length > 2}
                                            <p>
                                                Jumlah: <strong
                                                    >{line.replace(
                                                        "Jumlah: ",
                                                        "",
                                                    )}</strong
                                                >
                                            </p>
                                        {:else if msg.isIncome && i === 1}
                                            <p class="amount-highlight">
                                                {line}
                                            </p>
                                        {:else}
                                            <p>{line}</p>
                                        {/if}
                                    {/each}
                                {:else}
                                    {msg.text}
                                {/if}
                                <span class="msg-time">{msg.time}</span>
                            </div>
                        </div>
                    {/each}

                    <div style="height: 100px;"></div>
                </div>

                <!-- Floating Summary Card -->
                <div class="summary-card">
                    <div class="summary-row">
                        <span class="label">Pengeluaran Hari Ini</span>
                        <span class="value expense"
                            >{formatRp(totalExpense)}</span
                        >
                    </div>
                    <div class="divider"></div>
                    <div class="summary-row">
                        <span class="label">Saldo Bulan Ini</span>
                        <span class="value balance"
                            >{formatRp(currentBalance)}</span
                        >
                    </div>
                </div>

                <!-- Input Area -->
                <div class="input-area">
                    <div class="input-placeholder">
                        {#if inputValue}
                            <span style="color: #000;">{inputValue}</span><span
                                class="cursor">|</span
                            >
                        {:else}
                            Ketik pengeluaran...
                        {/if}
                    </div>
                    <div class="mic-button">
                        <svg
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            fill="white"
                            ><path
                                d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"
                            /><path
                                d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"
                            /></svg
                        >
                    </div>
                </div>

                <div class="home-indicator"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .iphone-container {
        padding: 0;
        display: flex;
        justify-content: center;
        transform: scale(0.85);
        transform-origin: top center;
    }

    .iphone {
        position: relative;
        width: 280px;
        height: 560px;
        background: #fbfbfb;
        border-radius: 40px;
        box-shadow:
            0 0 0 3px #d6d6d6,
            0 0 0 7px #1a1a1a,
            0 20px 40px -10px rgba(0, 0, 0, 0.4);
        box-sizing: content-box;
        z-index: 10;
    }

    .buttons {
        position: absolute;
        background: #1a1a1a;
        border-radius: 4px 0 0 4px;
        left: -9px;
    }
    .side-button {
        top: 100px;
        height: 30px;
        width: 3px;
    }
    .volume-up {
        top: 150px;
        height: 40px;
        width: 3px;
    }
    .volume-down {
        top: 200px;
        height: 40px;
        width: 3px;
    }

    .bezel {
        position: relative;
        width: 100%;
        height: 100%;
        background: #000;
        border-radius: 38px;
        padding: 8px;
        overflow: hidden;
    }

    .screen {
        width: 100%;
        height: 100%;
        background: #ece5dd;
        background-image: linear-gradient(#e5ddd5 50%, #e5ddd5 50%);
        border-radius: 32px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        font-family: var(--font-primary);
    }

    .status-bar {
        height: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        color: #000;
        font-size: 12px;
        font-weight: 600;
    }

    .notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 90px;
        height: 24px;
        background: #000;
        border-bottom-left-radius: 14px;
        border-bottom-right-radius: 14px;
        z-index: 51;
    }

    .status-bar .icons {
        display: flex;
        gap: 4px;
    }
    .icon {
        width: 14px;
        height: 14px;
    }

    .app-header {
        background: #fff;
        height: 75px;
        padding-top: 30px;
        padding-bottom: 8px;
        display: flex;
        align-items: center;
        padding-left: 10px;
        padding-right: 15px;
        border-bottom: 1px solid #e0e0e0;
        z-index: 40;
    }

    .back-arrow {
        font-size: 1.1rem;
        color: #007aff;
        margin-right: 6px;
    }
    .profile-info {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .avatar {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
    .info-text {
        display: flex;
        flex-direction: column;
    }
    .info-text .name {
        font-weight: 600;
        font-size: 14px;
        color: #111;
        gap: 4px;
        display: flex;
        align-items: center;
    }
    .badge {
        font-size: 8px;
        background: #eab308;
        color: #fff;
        padding: 1px 4px;
        border-radius: 4px;
    }
    .info-text .status {
        font-size: 10px;
        color: #10b981;
    }
    .actions {
        color: #007aff;
        font-size: 1.1rem;
    }

    .chat-area {
        flex: 1;
        padding: 12px 10px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        overflow-y: hidden;
        position: relative;
        background-color: #efe7de;
        padding-bottom: 150px;
    }

    .date-divider {
        align-self: center;
        background: #dce8f0;
        color: #555;
        font-size: 10px;
        padding: 3px 6px;
        border-radius: 6px;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .message {
        max-width: 85%;
        position: relative;
        font-size: 13px;
        line-height: 1.35;
    }
    .message.user {
        align-self: flex-end;
    }
    .message.user .bubble {
        background: #d9fdd3;
        border-radius: 12px 0 12px 12px;
        padding: 6px 10px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }
    .message.bot {
        align-self: flex-start;
    }
    .message.bot .bubble {
        background: #ffffff;
        border-radius: 0 12px 12px 12px;
        padding: 6px 10px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }
    .message p {
        margin: 0;
        margin-bottom: 1px;
    }
    .msg-time {
        display: block;
        text-align: right;
        font-size: 9px;
        color: #999;
        margin-top: 2px;
    }
    strong {
        font-weight: 600;
    }
    .amount-highlight {
        color: #10b981;
        font-weight: 700;
        font-size: 1.1em;
    }

    .summary-card {
        position: absolute;
        bottom: 70px;
        left: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border-radius: 14px;
        padding: 10px 14px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        border: 1px solid rgba(0, 0, 0, 0.05);
        z-index: 20;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .label {
        color: #64748b;
    }
    .value {
        font-weight: 700;
        color: #0f172a;
    }
    .value.expense {
        color: #ef4444;
    }
    .value.balance {
        color: #10b981;
    }

    .divider {
        height: 1px;
        background: #f1f5f9;
        margin: 6px 0;
    }

    .input-area {
        height: 60px;
        background: #f0f2f5;
        display: flex;
        align-items: flex-start;
        padding: 8px 12px;
        gap: 8px;
        position: absolute;
        bottom: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .input-placeholder {
        flex: 1;
        background: #fff;
        border-radius: 18px;
        padding: 6px 12px;
        font-size: 13px;
        color: #9ca3af;
        min-height: 32px;
        display: flex;
        align-items: center;
    }

    .cursor {
        animation: blink 1s infinite;
        color: #9ca3af;
        font-weight: 100;
    }
    @keyframes blink {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0;
        }
    }

    .mic-button {
        width: 32px;
        height: 32px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .home-indicator {
        position: absolute;
        bottom: 6px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: #000;
        border-radius: 2px;
        opacity: 0.2;
        z-index: 60;
    }
</style>
