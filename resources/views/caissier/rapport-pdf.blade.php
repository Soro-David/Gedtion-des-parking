<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Rapport Caissier</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; background: #fff; }

        .header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 10px; border-bottom: 3px solid #4A1725; }
        .header-left h1 { font-size: 17px; font-weight: 700; color: #4A1725; }
        .header-left p  { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .header-right   { text-align: right; font-size: 9px; color: #6b7280; }

        .meta { padding: 8px 20px; background: #f8f8f8; border-bottom: 1px solid #e5e7eb; display: flex; gap: 28px; }
        .meta-item label { font-size: 8px; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; display: block; }
        .meta-item span  { font-size: 10px; font-weight: 700; color: #111827; }

        .stats { display: flex; gap: 0; padding: 10px 20px; border-bottom: 1px solid #e5e7eb; }
        .stat-box { flex: 1; text-align: center; padding: 6px 8px; border-right: 1px solid #e5e7eb; }
        .stat-box:last-child { border-right: none; }
        .stat-box .val  { font-size: 13px; font-weight: 900; color: #111827; }
        .stat-box .lbl  { font-size: 8px; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; margin-top: 2px; }

        .table-wrap { padding: 12px 20px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #4A1725; color: #fff; }
        thead th { padding: 6px 6px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; font-size: 9px; }
        tfoot tr { background: #4A1725; color: #fff; font-weight: 900; }
        tfoot td { padding: 6px 6px; font-size: 10px; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 8px; font-weight: 700; }
        .badge-oui { background: #d1fae5; color: #065f46; }
        .badge-non { background: #f3f4f6; color: #6b7280; }
        .badge-occupied { background: #fee2e2; color: #991b1b; }

        .footer { margin-top: 14px; padding: 8px 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1>Rapport de Caisse & Performance Agents</h1>
        <p>Plateau Smart City — Parking Management</p>
    </div>
    <div class="header-right">
        <div>Généré le : <strong>{{ $generatedAt }}</strong></div>
        <div>Caissier : <strong>{{ $caissier }}</strong></div>
    </div>
</div>

<div class="meta">
    <div class="meta-item">
        <label>Période</label>
        <span>{{ $dateFrom }} → {{ $dateTo }}</span>
    </div>
    <div class="meta-item">
        <label>Agent filtré</label>
        <span>{{ $agentLabel ?? 'Tous les agents' }}</span>
    </div>
</div>

<div class="stats">
    <div class="stat-box">
        <div class="val">{{ $totalEntries }}</div>
        <div class="lbl">Entrées enregistrées</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ $totalExits }}</div>
        <div class="lbl">Sorties effectuées</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</div>
        <div class="lbl">Montant total généré</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($totalReverse, 0, ',', ' ') }} FCFA</div>
        <div class="lbl">Reversé</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($totalNonReverse, 0, ',', ' ') }} FCFA</div>
        <div class="lbl">Non reversé</div>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Plaque</th>
                <th>Véhicule</th>
                <th>Parking</th>
                <th>Agent Entrée</th>
                <th>Sortie Par</th>
                <th>Entrée</th>
                <th>Sortie</th>
                <th>Durée</th>
                <th style="text-align:right">Montant</th>
                <th style="text-align:center">Reversé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $i => $s)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $s['license_plate'] }}</strong></td>
                <td>{{ trim(($s['marque'] ?? '') . ' ' . ($s['modele'] ?? '')) ?: '—' }}</td>
                <td>{{ $s['parking'] ?? '—' }}</td>
                <td>{{ $s['agent_entry'] ?? '—' }}</td>
                <td>{{ $s['agent_exit'] ?? '—' }}</td>
                <td>{{ $s['started_at'] ?? '—' }}</td>
                <td>{{ $s['ended_at']   ?? 'En cours' }}</td>
                <td>{{ isset($s['duration_minutes']) ? $s['duration_minutes'] . ' min' : '—' }}</td>
                <td style="text-align:right"><strong>{{ number_format($s['amount'], 0, ',', ' ') }} FCFA</strong></td>
                <td style="text-align:center">
                    @if($s['status'] === 'occupied')
                        <span class="badge badge-occupied">En cours</span>
                    @elseif($s['versement_id'])
                        <span class="badge badge-oui">Oui</span>
                    @else
                        <span class="badge badge-non">Non</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9" style="text-align:right">TOTAL GÉNÉRÉ</td>
                <td style="text-align:right">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    Document généré automatiquement — Plateau Smart City © {{ date('Y') }}
</div>

</body>
</html>
