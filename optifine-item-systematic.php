<?php

// ITEM PUSHER
// Place of the textures: assets/minecraft/textures/item/itemtype/itemtype-counter.png
// Texture and CustomModelData connector: assets/minecraft/models/item/itemtype/itemtype-counter.json
// CustomModelData lobby / sorter: assets/minecraft/models/itemtype/itemtype-counter.json

// Clicking the upload button named "upload-file"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload-file'])) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];

            $itemType = $_GET['item-type']; // The itemType is based on the GET method, which means the data comes from a GET form's input 
            $targetDir = checkItemFolders($itemType);
            $itemTypeDir = $targetDir . "/" . $itemType;

            // Create the item-type directory if it doesnt exist yet
            if (!file_exists($itemTypeDir)) {
                mkdir($itemTypeDir, 0777, true);
            }

            // Get the details of the files
            $fileName = basename($file['name']);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $targetFile = $itemTypeDir . "/" . uniqid() . "." . $fileType; // Generate unique name

            // Allowed file types (honestly i dont know why i did this, texture files only working with .png files)
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            // Check the texture's size and it's type
            if ($file['size'] > 2 * 1024 * 1024) { // Max. 2MB
                $_SESSION['log'] .= "A fájl túl nagy! (Max: 2MB)";
            } elseif (!in_array($fileType, $allowedTypes)) {
                $_SESSION['log'] .= "Csak JPG, JPEG, PNG vagy GIF fájlok engedélyezettek.";
            } else {
                // Move the uploaded texture to upload directory
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $_SESSION['log'] .= "A fájl sikeresen feltöltve: $fileName";
                } else {
                    $_SESSION['log'] .= "Hiba történt a fájl feltöltése során.";
                }
            }
            
            $textureAm = checkTextures($itemType); // Gets the latest texture's number, and returns it so it can rename the newest one
            $renameTo = $itemTypeDir . "/" . $itemType . "-" . $textureAm . "." . $fileType; // Arranging the texture to the correct folder
            $_SESSION['log'] .= $renameTo; // was lasy building a logging system, its fine its fine
            rename($targetFile, $renameTo); // renames the texture-type file to the latest (f.e.: iron-sword-5.png)

            $itemHolding = isHandHeld($itemType);

            // DONT DO ANYTHING WITH THIS!
            // This is the code it gives to the JSON file which is connecting the Item to the Texture trough the CustomModelData connector
            
            $data = [
                "parent" => "item/$itemHolding",
                "textures" => [
                    "layer0" => "item/$itemType/$itemType-$textureAm"
                ]
            ]; 
            
            // return the data to the JSON file with the correct variable settings

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
                $_SESSION['log'] .= "JSON file created successfully: $jsonFilePath -- ";
            } else {
                $_SESSION['log'] .= "Beep boop error while uploading (probably incorrect permissions)!";
            }

            addCustomModelData($itemType);

            header("Location: admin-optifine-creator.php?item-type=" . $itemType); // Refresh the site
        } else {
            $_SESSION['log'] .= "No file selected, or something exploded in the background...";
        }
    }
}

// This function sets the base of the JSON file, after that it will continue generating the texture's CustomModelData based on its numeric ID stuff 
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

// This checks if it is a sword type item or else (IF YOU WANT TO USE THIS, AND GIVE AN OTHER ITEM TYPE WHICH IS HELD LIKE A SWORD BUT YOU DONT SEE IT HERE, ADD THIS INSIDE THE (): " || str_contains($itemtype, "idk what type of item, like crossbow")")
function isHandHeld($itemtype) {
    if (str_contains($itemtype, "sword") || str_contains($itemtype, "axe") || str_contains($itemtype, "hoe") || str_contains($itemtype, "shovel")) {
        return "handheld";
    } else {
        return "generated";
    }
}

// Goes trough the existing textures
function checkTextures($itemtype) {
    $itemFolderTextures = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/textures/item/";
    $itemFolderTexturePath = $itemFolderTextures . $itemtype . "/";
    $pngFiles = glob($itemFolderTexturePath . "*.png");

    return count($pngFiles);
}

// Checks the existing item folders, ALL 3 OF THEM
function checkItemFolders($itemtype) {
    $log = "Kérelem elindítva. -- ";
    $itemFolderTextures = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/textures/item/";
    $itemFolderTexturePath = $itemFolderTextures . $itemtype . "/";
    $itemFolderModels = $_SERVER['DOCUMENT_ROOT'] . "/kozpont/texturepack/assets/minecraft/models/item/";
    $itemFolderModelPathDIR = $itemFolderModels . $itemtype . "/";
    $itemFolderModelPathFILE = $itemFolderModels . $itemtype . ".json";

    if (file_exists($itemFolderTexturePath)) {
        $log .= "Item-type folder found successfully! -- ";
    } else {
        $log .= "There is no Item-type folder found! Creating new... -- ";
        if (mkdir($itemFolderTexturePath, 0755, true)) { // true: teljes hierarchiát létrehozza

            $log .= "Item-type registrating started successfully! -- ";

            if (!file_exists($itemFolderModelPathDIR) && mkdir($itemFolderModelPathDIR, 0755, true)) {
                $log .= "Item-type folder created successfully! -- ";
            } else {
                $log .= "Item-type folder isn't created for some reason! Abort! -- ";
            }
            
            if (!file_exists($itemFolderModels) && mkdir($itemFolderModels, 0755, true)) {
                $log .= "Successfully created the Item-type and the CustomModelData connector folder and its files! -- ";
            } else {
                $log .= "Item-type and CustomModelData connector folder creation is failed... -- ";
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
                $log = $log . "JSON file is created successfully: $itemFolderModelPathFILE -- ";
            } else {
                $log .= "Error while creating the JSON file!";
            }

            $log .= "NEW ITEM TYPE REGISTRATED! YIPPEE -- ";
        } else {
            $log .= "Error while creating new folder - try to give permissions if this system is running on a server! -- ";
        }
    }
    $_SESSION['log'] = $log;
    return $itemFolderTextures;
}


?>

<div class="container">
<?php include "partials/admin-back.php"; ?>
    <h1>Optifine JSON wizard</h1>

    <?php if (isset($_SESSION['log']) && $_SESSION['log'] !== ""): ?>
        <h5 class="alert" style="margin-bottom: 1rem;"><?php echo $_SESSION['log'] ?></h5> 
    <?php endif; ?>

    <form method="GET" class="profile-settings" name="item-type-choose">
        <input type="text" name="item-type" class="border color2" placeholder="Write the item's type! For example: iron_sword" value="<?php echo isset($_GET['item-type']) ? $_GET['item-type'] : "" ?>">
        <input type="submit" value="Item típus kiválasztása" class="button blue">
    </form>

    <?php if (isset($_GET['item-type'])): ?>
        <form method="POST" enctype="multipart/form-data" class="profile-settings">
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required>
        <button type="submit" name="upload-file" class="button blue">Upload</button>
    </form>
    <?php endif; ?>

<?php
if (isset($_GET['item-type'])) {
    $link = "https://localhost/kozpont/"; // Change the link if you want to
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
        <th>Texture</th>
        <th>Itemtype</th>
        <th>CustomModelData</th>
        <th>Order</th>
        <th>Operation</th>
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
            <a href="admin-optifine-editor.php?item=<?php echo str_replace(".png", "", $x) ?>" class="button green">Edit</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; if (isset($_SESSION['log'])) {
    unset($_SESSION['log']);
}?>



</div>
