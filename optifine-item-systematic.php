<?php

// ITEM PUSHER
// TEXTÚRÁK HELYE: assets/minecraft/textures/item/item-típus/típus-számláló
// TEXTÚRA ÉS CustomModelData összekötő: assets/minecraft/models/item/item-típus/típus-számláló
// CustomModelData elosztó: assets/minecraft/models/item/típus

include "partials/header.php";
include "partials/navbar.php";

// Initialize session log if not set
if (!isset($_SESSION['log'])) {
    $_SESSION['log'] = '';
}

// Feltöltés gomb megnyomása után
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload-file'])) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];

            $itemType = $_GET['item-type'];
            $targetDir = checkItemFolders($itemType);
            $itemTypeDir = $targetDir . "/" . $itemType;

            // Create the item-type directory if it doesn't exist
            if (!file_exists($itemTypeDir)) {
                mkdir($itemTypeDir, 0777, true);
            }

            // File details
            $fileName = basename($file['name']);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $targetFile = $itemTypeDir . "/" . uniqid() . "." . $fileType; // Generate unique name

            // Allowed file types
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            // Check file size and type
            if ($file['size'] > 2 * 1024 * 1024) { // Max. 2MB
                $_SESSION['log'] .= "A fájl túl nagy! (Max: 2MB)";
            } elseif (!in_array($fileType, $allowedTypes)) {
                $_SESSION['log'] .= "Csak JPG, JPEG, PNG vagy GIF fájlok engedélyezettek.";
            } else {
                // Move file to upload directory
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $_SESSION['log'] .= "A fájl sikeresen feltöltve: $fileName";
                } else {
                    $_SESSION['log'] .= "Hiba történt a fájl feltöltése során.";
                }
            }
            
            $textureAm = checkTextures($itemType);
            $renameTo = $itemTypeDir . "/" . $itemType . "-" . $textureAm . "." . $fileType;
            $_SESSION['log'] .= $renameTo;
            rename($targetFile, $renameTo);

            $itemHolding = isHandHeld($itemType);

            $data = [
                "parent" => "item/$itemHolding",
                "textures" => [
                    "layer0" => "item/$itemType/$itemType-$textureAm"
                ]
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $modelDir = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/models/item/";
            $modelDirJSON = $modelDir . $itemType . "/";
            $modelDir = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/models/item/";
            $modelDirJSON = $modelDir . $itemType . "/";

            // Create the directory if it doesn't exist
            if (!file_exists($modelDirJSON)) {
                mkdir($modelDirJSON, 0777, true);
            }

            $jsonFilePath = $modelDirJSON . $itemType . "-" . $textureAm . ".json";

            if (file_put_contents($jsonFilePath, $jsonData)) {
                $_SESSION['log'] .= "JSON fájl sikeresen létrehozva: $jsonFilePath -- ";
            } else {
                $_SESSION['log'] .= "Hiba történt a JSON fájl létrehozásakor!";
            }

            addCustomModelData($itemType);

            header("Location: admin-optifine-creator.php?item-type=" . $itemType);
        } else {
            $_SESSION['log'] .= "Nincs kiválasztva fájl, vagy hiba történt.";
        }
    }
}

function addCustomModelData($itemType) {
    $data = [
        "parent" => "item/handheld",
        "textures" => [
            "layer0" => "item/$itemType/$itemType-1"
        ],
        "overrides" => [
            [
                "predicate" => [
                    "custom_model_data" => 1
                ],
                "model" => "item/$itemType/$itemType-1"
            ]
        ]
    ];
    
    // Add new overrides dynamically
    $textureAm = checkTextures($itemType);
    for ($i = 2; $i <= $textureAm; $i++) {
        $data['overrides'][] = [
            "predicate" => [
                "custom_model_data" => $i
            ],
            "model" => "item/$itemType/$itemType-$i"
        ];
    }
    
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    $modelDir = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/models/item/";
    $modelDirJSON = $modelDir . $itemType . "/";
    
    // Create the directory if it doesn't exist
    if (!file_exists($modelDirJSON)) {
        mkdir($modelDirJSON, 0777, true);
    }
    
    $jsonFilePath = $modelDir . $itemType . ".json";
    
    if (file_put_contents($jsonFilePath, $jsonData)) {
        $_SESSION['log'] .= "JSON fájl sikeresen létrehozva: $jsonFilePath -- ";
    } else {
        $_SESSION['log'] .= "Hiba történt a JSON fájl létrehozásakor!";
    }
}
function isHandHeld($itemtype) {
    if (str_contains($itemtype, "sword")) {
        return "handheld";
    } else {
        return "generated";
    }
}
function checkTextures($itemtype) {
    $itemFolderTextures = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/textures/item/";
    $itemFolderTexturePath = $itemFolderTextures . $itemtype . "/";
    $pngFiles = glob($itemFolderTexturePath . "*.png");

    return count($pngFiles);
}
function checkItemFolders($itemtype) {
    $log = "Kérelem elindítva. -- ";
    $itemFolderTextures = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/textures/item/";
    $itemFolderTexturePath = $itemFolderTextures . $itemtype . "/";
    $itemFolderModels = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/models/item/";
    $itemFolderModelPathDIR = $itemFolderModels . $itemtype . "/";
    $itemFolderModelPathFILE = $itemFolderModels . $itemtype . ".json";

    if (file_exists($itemFolderTexturePath)) {
        $log .= "Item-típus textúra mappa megtalálva! -- ";
    } else {
        $log .= "Nem létező Item-típus texúra mappa, feldolgozás... -- ";
        if (mkdir($itemFolderTexturePath, 0755, true)) { // true: teljes hierarchiát létrehozza

            $log .= "Item-típus regisztrálás elindítva! -- ";

            if (!file_exists($itemFolderModelPathDIR) && mkdir($itemFolderModelPathDIR, 0755, true)) {
                $log .= "Sikeres Item-típus textúramappa létrehozás! -- ";
            } else {
                $log .= "Item-típus textúramappa létrehozása sikertelen! -- ";
            }
            
            if (!file_exists($itemFolderModels) && mkdir($itemFolderModels, 0755, true)) {
                $log .= "Sikeres Item-típus és CustomModelData összekötő mappa létrehozás! -- ";
            } else {
                $log .= "Item-típus és CustomModelData összekötő mappa létrehozása sikertelen! -- ";
            }

            $itemHolding = isHandHeld($itemtype);


            $data = [
                "parent" => "item/$itemHolding",
                "textures" => [
                    "layer0" => "item/$itemtype"
                ],
                "overrides" => [
                    [
                        "predicate" => [
                            "custom_model_data" => 1
                        ],
                        "model" => "item/$itemtype/$itemtype-1"
                    ]
                ]
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (file_put_contents($itemFolderModelPathFILE, $jsonData)) {
                $log = $log . "JSON fájl sikeresen létrehozva: $itemFolderModelPathFILE -- ";
            } else {
                $log .= "Hiba történt a JSON fájl létrehozásakor!";
            }

            $log .= "Új Item-típus regisztrálva! -- ";
        } else {
            $log .= "Hiba történt az új mappa létrehozásakor! -- ";
        }
    }
    $_SESSION['log'] = $log;
    return $itemFolderTextures;
}


?>

<div class="container">
<?php include "partials/admin-back.php"; ?>
    <h1>Optifine JSON varázsló</h1>

    <?php if (isset($_SESSION['log']) && $_SESSION['log'] !== ""): ?>
        <h5 class="alert" style="margin-bottom: 1rem;"><?php echo $_SESSION['log'] ?></h5> 
    <?php endif; ?>

    <form method="GET" class="profile-settings" name="item-type-choose">
        <input type="text" name="item-type" class="border color2" placeholder="Írd be az item típusát! Pl: iron_sword" value="<?php echo isset($_GET['item-type']) ? $_GET['item-type'] : "" ?>">
        <input type="submit" value="Item típus kiválasztása" class="button blue">
    </form>

    <?php if (isset($_GET['item-type'])): ?>
        <form method="POST" enctype="multipart/form-data" class="profile-settings">
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required>
        <button type="submit" name="upload-file" class="button blue">Feltöltés</button>
    </form>
    <?php endif; ?>

<?php
if (isset($_GET['item-type'])) {
    $link = "https://obscurenetwork.hu/kozpont/";
    $path = "texturepack/assets/minecraft/textures/item/";
    if (is_dir($path . $_GET['item-type'] . "/")) {
        $dir = scandir($path . $_GET['item-type'] . "/", 1); 
    }
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
}
?>

<?php if(isset($dir)): ?>
    <table>
    <tr>
        <th>Textúra</th>
        <th>Itemtípus</th>
        <th>CustomModelData</th>
        <th>Sorrend</th>
        <th>Művelet</th>
    </tr>
    <?php 
    $files = [];
    foreach($dir as $x) {
        $fileType = strtolower(pathinfo($x, PATHINFO_EXTENSION));
        if ($x !== "." && $x !== ".." && in_array($fileType, $allowedTypes)) {
            $files[] = $x;
        }
    }
    natsort($files);
    $i = 0;
    foreach($files as $x): $i++; ?>
    <tr>
        <td> <img src="<?php echo $link . $path . $_GET['item-type'] . "/" . $x; ?>" style="width: 3rem; height: 3rem; image-rendering: pixelated;"> </td>
        <td><?php echo $_GET['item-type']; ?></td>
        <td><?php echo pathinfo($x, PATHINFO_FILENAME); ?></td>
        <td><?php echo $i ?></td>
        <td>
            <a href="admin-optifine-editor.php?item=<?php echo str_replace(".png", "", $x) ?>" class="button green">Szerkesztés</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; if (isset($_SESSION['log'])) {
    unset($_SESSION['log']);
}?>



</div>
