$old = '#4A1725'
$new = '#0C3EAD'
Get-ChildItem -Path . -Recurse -Include *.vue,*.js,*.css -File | ForEach-Object {
    (Get-Content $_ -Raw) -replace [regex]::Escape($old), $new | Set-Content $_
}
Write-Host "Color replacement completed."
