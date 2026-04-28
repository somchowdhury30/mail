<?php
// B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aether | Global Inbox Feed</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        dark: '#0a0b10',
                        card: '#12141c',
                        neon: '#00f0ff',
                        success: '#00ffcc',
                        danger: '#ff0055',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dark text-white min-h-screen relative overflow-x-hidden font-sans">
    <div class="fixed top-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full bg-[radial-gradient(circle,rgba(0,240,255,0.15)_0%,rgba(10,11,16,0)_70%)] blur-[120px] -z-10 pointer-events-none"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(138,43,226,0.15)_0%,rgba(10,11,16,0)_70%)] blur-[120px] -z-10 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto p-4 sm:p-8">
        <header class="flex flex-col sm:flex-row justify-between items-center mb-8 gap-4 text-center sm:text-left">
            <div class="flex items-center gap-3 text-2xl font-bold tracking-tight">
                <span>⚡</span>
                <span>Aether <span class="text-neon drop-shadow-[0_0_8px_rgba(0,240,255,0.8)]">Inbox Feed</span></span>
                <span class="text-[0.6rem] ml-2 px-2 py-0.5 rounded border border-success/30 bg-success/10 text-success">v1.1 Elite</span>
            </div>
            <div class="flex items-center gap-2 px-4 py-1.5 rounded-full border border-success/20 bg-success/10 text-success text-sm font-medium">
                <div class="w-2 h-2 rounded-full bg-success shadow-[0_0_8px_#00ffcc] animate-[pulse_1.5s_infinite]"></div>
                <span id="last-updated">Live Sync Active</span>
            </div>
        </header>

        <main>
            <div class="mb-8 bg-card/40 backdrop-blur-md border border-neon/15 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="email" id="alias-input" placeholder="Enter your alias email (e.g. yourname+koala00@gmail.com)" class="flex-grow bg-black/30 border border-neon/20 rounded-xl px-5 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-neon/50 focus:shadow-[0_0_15px_rgba(0,240,255,0.1)] transition-all" />
                    <button id="generate-btn" class="px-6 py-3 bg-white/10 hover:bg-white/15 text-white border border-white/20 font-semibold rounded-xl transition-all active:scale-95">🎲 Generate Random</button>
                    <button id="fetch-btn" class="px-6 py-3 bg-neon hover:bg-[#00d4ff] text-dark font-semibold rounded-xl shadow-[0_0_20px_rgba(0,240,255,0.3)] transition-all active:scale-95">Check Inbox</button>
                </div>
                <div class="mt-3 text-sm text-slate-400">You will only see emails sent specifically to this address.</div>
            </div>

            <div class="grid grid-cols-1 gap-6" id="inbox-container">
                <div class="bg-card/65 backdrop-blur-xl border border-neon/15 rounded-2xl p-6 animate-pulse">
                    <div class="h-6 bg-white/10 rounded w-1/3 mb-4"></div>
                    <div class="h-4 bg-white/10 rounded w-full mb-2"></div>
                    <div class="h-4 bg-white/10 rounded w-5/6 mb-2"></div>
                    <div class="h-4 bg-white/10 rounded w-4/6"></div>
                </div>
            </div>
        </main>
    </div>

    <div id="email-modal" class="fixed inset-0 bg-dark/80 backdrop-blur-md flex items-end sm:items-center justify-center z-50 opacity-0 invisible transition-all duration-300 [&.active]:opacity-100 [&.active]:visible p-0 sm:p-4">
        <div class="relative w-full sm:max-w-2xl bg-card border border-neon/15 rounded-t-[24px] sm:rounded-[16px] shadow-[0_20px_40px_rgba(0,0,0,0.4)] flex flex-col transform translate-y-full sm:translate-y-8 transition-transform duration-300" id="modal-content-box">
            
            <div class="absolute top-3 left-1/2 -translate-x-1/2 w-10 h-1.5 bg-white/30 rounded-full sm:hidden"></div>
            
            <div class="flex justify-between items-center pt-8 sm:pt-6 pb-6 px-6 border-b border-neon/15">
                <h3 id="modal-subject" class="text-xl font-semibold text-white pr-8 break-words">Subject</h3>
                <button id="modal-close" class="text-slate-400 hover:text-white text-3xl leading-none transition-colors">&times;</button>
            </div>
            <div class="p-6 border-b border-neon/15 bg-white/5 flex flex-col gap-2">
                <div class="flex gap-4 text-sm">
                    <span class="text-slate-400 min-w-[50px]">From:</span>
                    <span id="modal-sender" class="text-white font-medium break-all">Sender Name</span>
                </div>
                <div class="flex gap-4 text-sm">
                    <span class="text-slate-400 min-w-[50px]">Date:</span>
                    <span id="modal-date" class="text-white font-medium break-all">Date</span>
                </div>
            </div>
            <div class="p-6 overflow-y-auto overflow-x-hidden flex-grow w-full max-h-[60vh] sm:max-h-[50vh]">
                <div id="modal-body" class="text-slate-300 leading-relaxed text-[0.95rem] whitespace-pre-line break-words max-w-full w-full email-body-content"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const modal = document.getElementById('email-modal');
        const modalContent = document.getElementById('modal-content-box');
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    if (modal.classList.contains('active')) {
                        modalContent.classList.remove('translate-y-full', 'sm:translate-y-8');
                        modalContent.classList.add('translate-y-0');
                    } else {
                        modalContent.classList.remove('translate-y-0');
                        modalContent.classList.add('translate-y-full', 'sm:translate-y-8');
                    }
                }
            });
        });
        observer.observe(modal, { attributes: true });
    </script>
</body>
</html>
