-- Framework detection
local Framework = nil
local FrameworkName = nil

-- Auto-detect framework
if Config.Framework == 'auto' then
    if GetResourceState('qbox-core') == 'started' or GetResourceState('qbx_core') == 'started' then
        Framework = exports.qbx_core
        FrameworkName = 'qbox'
    elseif GetResourceState('qb-core') == 'started' then
        Framework = exports['qb-core']:GetCoreObject()
        FrameworkName = 'qbcore'
    elseif GetResourceState('es_extended') == 'started' then
        Framework = exports['es_extended']:getSharedObject()
        FrameworkName = 'esx'
    end
elseif Config.Framework == 'qbox' then
    Framework = exports.qbx_core
    FrameworkName = 'qbox'
elseif Config.Framework == 'qbcore' then
    Framework = exports['qb-core']:GetCoreObject()
    FrameworkName = 'qbcore'
elseif Config.Framework == 'esx' then
    Framework = exports['es_extended']:getSharedObject()
    FrameworkName = 'esx'
end

-- Framework-specific player getter
local function GetPlayer(source)
    if FrameworkName == 'qbox' then
        return Framework:GetPlayer(source)
    elseif FrameworkName == 'qbcore' then
        return Framework.Functions.GetPlayer(source)
    elseif FrameworkName == 'esx' then
        return Framework.GetPlayerFromId(source)
    end
    return nil
end

-- Framework-specific character data extraction
local function GetCharacterData(Player)
    if not Player then return nil end
    
    if FrameworkName == 'qbox' then
        return {
            firstName = Player.PlayerData.charinfo.firstname,
            lastName = Player.PlayerData.charinfo.lastname,
            cid = Player.PlayerData.citizenid,
            job = Player.PlayerData.job.name
        }
    elseif FrameworkName == 'qbcore' then
        return {
            firstName = Player.PlayerData.charinfo.firstname,
            lastName = Player.PlayerData.charinfo.lastname,
            cid = Player.PlayerData.citizenid,
            job = Player.PlayerData.job.name
        }
    elseif FrameworkName == 'esx' then
        return {
            firstName = Player.variables.firstName or Player.get('firstName'),
            lastName = Player.variables.lastName or Player.get('lastName'),
            cid = Player.identifier,
            job = Player.job.name
        }
    end
    
    return nil
end

-- Get character data from framework
if FrameworkName == 'qbox' then
    Framework:CreateCallback('courttablet:getCharacterData', function(source, cb)
        local src = source
        local Player = GetPlayer(src)
        
        if Player then
            local characterData = GetCharacterData(Player)
            
            if Config.Debug then
                print("Server sending QBox character data:", json.encode(characterData))
            end
            
            cb(characterData)
        else
            if Config.Debug then
                print("No QBox player data found for source:", src)
            end
            cb({error = "No player data found"})
        end
    end)
elseif FrameworkName == 'qbcore' then
    Framework.Functions.CreateCallback('courttablet:getCharacterData', function(source, cb)
        local src = source
        local Player = GetPlayer(src)
        
        if Player then
            local characterData = GetCharacterData(Player)
            
            if Config.Debug then
                print("Server sending QBCore character data:", json.encode(characterData))
            end
            
            cb(characterData)
        else
            if Config.Debug then
                print("No QBCore player data found for source:", src)
            end
            cb({error = "No player data found"})
        end
    end)
elseif FrameworkName == 'esx' then
    Framework.RegisterServerCallback('courttablet:getCharacterData', function(source, cb)
        local src = source
        local Player = GetPlayer(src)
        
        if Player then
            local characterData = GetCharacterData(Player)
            
            if Config.Debug then
                print("Server sending ESX character data:", json.encode(characterData))
            end
            
            cb(characterData)
        else
            if Config.Debug then
                print("No ESX player data found for source:", src)
            end
            cb({error = "No player data found"})
        end
    end)
end

-- Alternative method using database query
RegisterServerEvent('courttablet:getCharacterDataFromDB')
AddEventHandler('courttablet:getCharacterDataFromDB', function()
    local src = source
    local Player = GetPlayer(src)
    
    if Player then
        local identifier = nil
        local tableName = nil
        local columnName = nil
        
        if FrameworkName == 'qbox' then
            identifier = Player.PlayerData.citizenid
            tableName = 'players'
            columnName = 'citizenid'
        elseif FrameworkName == 'qbcore' then
            identifier = Player.PlayerData.citizenid
            tableName = 'players'
            columnName = 'citizenid'
        elseif FrameworkName == 'esx' then
            identifier = Player.identifier
            tableName = 'users'
            columnName = 'identifier'
        end
        
        if identifier and tableName and columnName then
            local query = string.format('SELECT * FROM %s WHERE %s = @identifier', tableName, columnName)
            
            MySQL.Async.fetchAll(query, {
                ['@identifier'] = identifier
            }, function(result)
                if result[1] then
                    local characterData = nil
                    
                    if FrameworkName == 'qbox' or FrameworkName == 'qbcore' then
                        local charinfo = json.decode(result[1].charinfo)
                        if charinfo and charinfo.firstname and charinfo.lastname then
                            characterData = {
                                firstName = charinfo.firstname,
                                lastName = charinfo.lastname,
                                cid = identifier
                            }
                        end
                    elseif FrameworkName == 'esx' then
                        characterData = {
                            firstName = result[1].firstname,
                            lastName = result[1].lastname,
                            cid = identifier
                        }
                    end
                    
                    if characterData then
                        if Config.Debug then
                            print("Got character data from database:", json.encode(characterData))
                        end
                        
                        TriggerClientEvent('courttablet:receiveCharacterData', src, characterData)
                    else
                        TriggerClientEvent('courttablet:receiveCharacterData', src, {error = "Invalid character data"})
                    end
                else
                    TriggerClientEvent('courttablet:receiveCharacterData', src, {error = "Character not found in database"})
                end
            end)
        else
            TriggerClientEvent('courttablet:receiveCharacterData', src, {error = "Framework not supported"})
        end
    end
end)

-- Framework notification function
RegisterServerEvent('courttablet:notify')
AddEventHandler('courttablet:notify', function(message, type)
    local src = source
    
    if FrameworkName == 'qbox' then
        TriggerClientEvent('qbx_core:notify', src, message, type)
    elseif FrameworkName == 'qbcore' then
        TriggerClientEvent('QBCore:Notify', src, message, type)
    elseif FrameworkName == 'esx' then
        TriggerClientEvent('esx:showNotification', src, message)
    end
end)

-- Debug info
if Config.Debug then
    print("^2[sd-tablet]^7 Server framework detected: " .. (FrameworkName or "None"))
    if not Framework then
        print("^1[sd-tablet]^7 ERROR: No supported framework found!")
    end
end
