@props(['direction' => 0])

{{--
    Ini adalah Blade Component untuk SVG Kompas.
    Ia menerima satu properti: 'direction', yaitu arah angin dalam derajat (0-360).
--}}
<svg class="compass" viewBox="0 0 100 100">
    <!-- Lingkaran luar dan tanda mata angin -->
    <circle cx="50" cy="50" r="48" stroke="#e0e0e0" stroke-width="2" fill="white" />
    <text x="50" y="12" text-anchor="middle" font-size="9" fill="#333">N</text>
    <text x="88" y="54" text-anchor="middle" font-size="9" fill="#333">E</text>
    <text x="50" y="92" text-anchor="middle" font-size="9" fill="#333">S</text>
    <text x="12" y="54" text-anchor="middle" font-size="9" fill="#333">W</text>

    <!-- Jarum Kompas (sebagai grup agar mudah diputar) -->
    <g id="compass-needle"
        style="transform-origin: 50px 50px;
              transition: transform 0.5s ease-in-out;
              transform: rotate({{ $direction }}deg);">
        <polygon points="50,10 55,50 45,50" fill="#dc3545" />
        <polygon points="50,90 55,50 45,50" fill="#343a40" />
    </g>
</svg>
