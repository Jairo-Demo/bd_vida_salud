<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 14px; }
    .page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
    .page-subtitle { font-size: 13px; color: #64748b; margin-top: 2px; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .card-title { font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #0284c7; margin-bottom: 12px; }
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px 16px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-label { font-size: 12px; font-weight: 600; color: #334155; }
    .form-control { padding: 8px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; width: 100%; }
    .form-control:focus { border-color: #0284c7; outline: none; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; }
    .btn { display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; text-decoration: none; }
    .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; }
    .btn-light { background: #f1f5f9; color: #475569; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-sm { padding: 5px 10px; font-size: 11.5px; border-radius: 6px; }
    .act { display: inline-block; border: none; cursor: pointer; font-size: 11.5px; font-weight: 600; padding: 5px 9px; border-radius: 6px; margin: 1px 2px; text-decoration: none; }
    .act-info { background: #e0f2fe; color: #0369a1; }
    .act-ok { background: #dcfce7; color: #166534; }
    .act-warn { background: #fef3c7; color: #92400e; }
    .act-del { background: #fee2e2; color: #b91c1c; }
    .act-att { background: #ede9fe; color: #5b21b6; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase; text-align: left; }
    .data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .badge { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
    .b-on { background: #dcfce7; color: #15803d; } .b-off { background: #f1f5f9; color: #64748b; }
    .b-leve { background: #fef9c3; color: #854d0e; } .b-moderada { background: #ffedd5; color: #9a3412; } .b-grave { background: #fee2e2; color: #991b1b; }
    .notice { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
    .notice-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .notice-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .notice-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    .row-detail { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 13px; border-bottom: 1px solid #f8fafc; }
    .lbl { color: #64748b; font-weight: 500; } .val { color: #0f172a; font-weight: 600; text-align: right; }
    .tabs { display: flex; gap: 6px; margin-bottom: 16px; }
    .tab { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; background: #f1f5f9; color: #475569; text-decoration: none; }
    .tab.on { background: #0284c7; color: #fff; }
    .muted { color: #94a3b8; font-size: 12.5px; }
</style>
