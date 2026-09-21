<?php
$file = 'includes/footer.php';
$content = file_get_contents($file);

$search = <<<HTML
                  <!-- Developer -->
                  <div class="flex items-center justify-between p-4 bg-[#2d3748] rounded-xl border border-[#4a5568]">
                      <div class="flex items-center">
                          <div class="w-10 h-10 bg-[#4C1D95] text-[#A78BFA] rounded-full flex items-center justify-center text-xl mr-4 flex-shrink-0">
                              <ion-icon name="code-slash-outline"></ion-icon>
                          </div>
                          <div>
                              <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">DEVELOPER</p>
                              <p class="text-white font-bold text-sm">Pearlwaves</p>
                          </div>
                      </div>
                      <div class="flex gap-2">
                          <a href="https://wa.me/94775768610" target="_blank" class="w-8 h-8 bg-[#1a202c] hover:bg-[#38A169] text-gray-300 hover:text-white rounded-full flex items-center justify-center transition">
                              <ion-icon name="logo-whatsapp"></ion-icon>
                          </a>
                          <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" class="w-8 h-8 bg-[#1a202c] hover:bg-[#2563EB] text-gray-300 hover:text-white rounded-full flex items-center justify-center transition">
                              <ion-icon name="logo-facebook"></ion-icon>
                          </a>
                      </div>
                  </div>
HTML;

$replace = <<<HTML
                  <!-- Facebook -->
                  <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" class="flex items-center p-4 bg-[#2d3748] hover:bg-[#39455a] rounded-xl transition border border-[#4a5568]">
                      <div class="w-10 h-10 bg-[#1e3a8a] text-[#3b82f6] rounded-full flex items-center justify-center text-xl mr-4 flex-shrink-0">
                          <ion-icon name="logo-facebook"></ion-icon>
                      </div>
                      <div>
                          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">FACEBOOK</p>
                          <p class="text-white font-bold text-sm">Visit our Page</p>
                      </div>
                  </a>
HTML;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Updated footer modal.\n";
?>

