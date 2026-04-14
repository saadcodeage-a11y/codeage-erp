<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; margin: 28px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        h2 { font-size: 15px; margin: 24px 0 6px; }
        p { margin: 0 0 6px; color: #4b5563; }
        .meta { margin-bottom: 16px; }
        .summary-grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 12px 0 16px; }
        .summary-grid td { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; vertical-align: top; }
        .summary-grid span { display: block; color: #6b7280; font-size: 10px; margin-bottom: 4px; }
        .summary-grid strong { font-size: 15px; color: #111827; }
        .filter-table, .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .filter-table td { border: 1px solid #e5e7eb; padding: 8px 10px; }
        .filter-table td:first-child { width: 180px; font-weight: 700; background: #f9fafb; }
        .data-table th, .data-table td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; vertical-align: top; }
        .data-table th { background: #f9fafb; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .section { margin-top: 24px; }
        .preline { white-space: pre-line; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if(!empty($subtitle))
        <p class="meta">{{ $subtitle }}</p>
    @endif
    <p>{{ $description }}</p>

    @if(!empty($filterSummary))
        <table class="filter-table">
            <tbody>
                @foreach($filterSummary as $label => $value)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($summaryCards))
        <table class="summary-grid">
            <tr>
                @foreach($summaryCards as $card)
                    <td>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if(!empty($secondarySummaryCards))
        <table class="summary-grid">
            <tr>
                @foreach($secondarySummaryCards as $card)
                    <td>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if(!empty($table))
        <div class="section">
            <h2>{{ $table['title'] }}</h2>
            <p>{{ $table['description'] }}</p>
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach($table['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($table['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="preline">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @foreach($sections as $section)
        <div class="section">
            <h2>{{ $section['title'] }}</h2>
            <p>{{ $section['description'] }}</p>
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach($section['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="preline">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
