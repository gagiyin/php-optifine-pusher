# php-optifine-pusher
On my Minecraft server, we use texture packs to solve the problem of multiple weapons - we use Optifine for this, but we had to write some kind of system to create the files, because without it, it's a very long process to create the files one by one and supplement the existing ones.

# What this system does?
- When giving the itemtype into the input field, the code searches for the existing item folders and files;
- After that it will list all the existing files inside the itemtype's folder;
- Then if we upload a texture, it will:
  - Place the uploaded image into the texture folder and rename the texture (assets/minecraft/textures/item/itemtype/itemtype-counter.png);
  - It will create the connecting JSON files, which links the model to the texture (assets/minecraft/models/item/itemtype/itemtype-counter.json);
  - It will write a new line into the JSON file, which links the model-to-texture files to the item and its texture itself (basically it assigns the CustomModelData number) (assets/minecraft/models/itemtype/itemtype-counter.json);
 
As I said before, its a bit complicated, but it works great, and operates fully.

Right now if you want to use the system, you have to copy-paste my code and make it work with your surroundings, sry for that, it's made for my Admins only.

DISCLAIMER:
IF YOU MANAGE TO USE THIS SYSTEM:
- Change ALL THE PATHS to the right one!
- Change ALL THE GIVEN LINKS to yours!
- IT ONLY WORKS WITH ITEMS! Dont try it with any kind of armor or block, because it will make your game's textures go wild for sure
- Thats all i guess, hit me up if you need any kind of help with it!
