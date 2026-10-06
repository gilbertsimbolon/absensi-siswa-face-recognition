@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $delta = 1;

        $pageList = [];
        if ($last <= 7) {
            for ($i = 1; $i <= $last; $i++) {
                $pageList[] = $i;
            }
        } else {
            $left = max(2, $current - $delta);
            $right = min($last - 1, $current + $delta);

            if ($current <= 3) {
                $left = 2;
                $right = 3;
            }

            if ($current >= $last - 2) {
                $left = $last - 2;
                $right = $last - 1;
            }

            $pageList[] = 1;

            if ($left > 2) {
                $pageList[] = '...';
            }

            for ($i = $left; $i <= $right; $i++) {
                $pageList[] = $i;
            }

            if ($right < $last - 1) {
                $pageList[] = '...';
            }

            $pageList[] = $last;
        }
    @endphp

    <nav role="navigation" aria-label="Navigasi Halaman">
        <ul class="pagination pagination-sm mb-0">
            {{-- Tombol Halaman Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
                </li>
            @endif

            {{-- Elemen Penomoran Halaman Ringkas --}}
            @foreach ($pageList as $item)
                @if ($item === '...')
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">&hellip;</span>
                    </li>
                @elseif ($item == $current)
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">{{ $item }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($item) }}">{{ $item }}</a>
                    </li>
                @endif
            @endforeach

            {{-- Tombol Halaman Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
