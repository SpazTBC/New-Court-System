fx_version 'cerulean'
game 'gta5'

name 'sd-tablet'
description 'Court System Tablet for QBCore and ESX'
author 'Shawns Developments'
version '1.0.0'

-- Add MySQL dependency
dependency 'mysql-async'

-- Optional dependencies for frameworks
dependencies {
    'mysql-async'
}

shared_scripts {
    'config.lua'
}

client_scripts {
    'client/main.lua'
}

server_scripts {
    '@mysql-async/lib/MySQL.lua',
    'server/main.lua'
}

ui_page 'html/index.html'

files {
    'html/index.html',
    'html/css/style.css',
    'html/js/script.js'
}