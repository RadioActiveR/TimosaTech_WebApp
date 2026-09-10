<?php

function icon(string $name): void {
    $icons = [
        "monitor"  => '<path d="M4 5h16v10H4z"/><path d="M9 19h6M12 15v4"/>',
        "printer"  => '<path d="M6 9V4h12v5"/><rect x="4" y="9" width="16" height="7" rx="1"/><path d="M6 16h12v4H6z"/>',
        "tool"     => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5z"/>',
        "network"  => '<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13 6 17M12 13l6 4"/>',
        "shield"   => '<path d="M12 3l7 3v6c0 4.4-3 7.9-7 9-4-1.1-7-4.6-7-9V6z"/>',
        "headset"  => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/>',
        "badge"    => '<circle cx="12" cy="9" r="5"/><path d="M9 13.5 7.5 21 12 18.5 16.5 21 15 13.5"/>',
        "infinity" => '<path d="M7 15a3.5 3.5 0 1 1 0-7c2.5 0 3.5 3.5 5 3.5s2.5-3.5 5-3.5a3.5 3.5 0 1 1 0 7c-2.5 0-3.5-3.5-5-3.5s-2.5 3.5-5 3.5z"/>',
        "tower"    => '<rect x="7" y="3" width="10" height="18" rx="1.5"/><path d="M10 7h4M10 11h4M10 15h1.5"/>',
        "router"   => '<rect x="3" y="10" width="18" height="7" rx="1.5"/><path d="M7 10V7a2 2 0 0 1 2-2M17 10V7a2 2 0 0 0-2-2M7 17v2M11 17v2M15 17v2"/>',

        "facebook"  => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M13.5 12h1.25l.5-2.5h-1.75V8.25c0-.655 0-1.25 1.25-1.25h.75V4.845c-.163-.022-.802-.095-1.475-.095-1.48 0-2.522.903-2.522 2.565V9.5H10v2.5h1.515V18h2v-6z" fill="#00E5FF" stroke="none"/>',
        "twitter"   => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M17.33 8.26a4.8 4.8 0 0 1-1.41.39 2.5 2.5 0 0 0 1.08-1.36 5 5 0 0 1-1.7.62 2.5 2.5 0 0 0-4.25 2.28 7.08 7.08 0 0 1-5.16-2.61 2.5 2.5 0 0 0 .77 3.33 2.46 2.46 0 0 1-1.13-.31v.03a2.5 2.5 0 0 0 2 2.45 2.5 2.5 0 0 1-1.13.04 2.5 2.5 0 0 0 2.33 1.74 5.02 5.02 0 0 1-3.7 1.04 7.07 7.07 0 0 0 3.83 1.12c4.6 0 7.12-3.81 7.12-7.12v-.32a5.1 5.1 0 0 0 1.25-1.29z" fill="#00E5FF" stroke="none"/>',
        "linkedin"  => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M8.33 7.33a1 1 0 1 0 0 2 1 1 0 0 0 0-2zM7.5 10.33h1.67V16.67H7.5V10.33zm4 0h1.6v.87h.03a1.76 1.76 0 0 1 1.57-.87c1.68 0 1.97 1.1 1.97 2.54v3.8H15.03v-3.35c0-.8-.01-1.83-1.1-1.83-1.1 0-1.28.87-1.28 1.77v3.41H11.5v-6.34z" fill="#00E5FF" stroke="none"/>',
        "instagram" => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><rect x="6.67" y="6.67" width="10.66" height="10.66" rx="3" stroke="#00E5FF" stroke-width="1.3" fill="none"/><circle cx="12" cy="12" r="2.67" stroke="#00E5FF" stroke-width="1.3" fill="none"/><circle cx="15" cy="9" r="0.67" fill="#00E5FF" stroke="none"/>',
    ];
    echo '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
        . ($icons[$name] ?? $icons["monitor"]) . '</svg>';
}

?>