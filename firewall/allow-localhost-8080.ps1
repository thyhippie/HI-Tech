# Run PowerShell as Administrator before starting the local PHP server.
$ruleName = 'Hi-Tech Savvy PHP localhost 8080'

Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue | Remove-NetFirewallRule
New-NetFirewallRule `
    -DisplayName $ruleName `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8080 `
    -RemoteAddress '127.0.0.1','::1' `
    -Profile Any