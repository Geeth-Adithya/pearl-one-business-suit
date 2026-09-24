<?php
$file = 'includes/footer.php';
$content = file_get_contents($file);

$contactWidget = <<<HTML
<!-- Floating Contact Button & Modal -->
<div x-data="{ contactOpen: false }" class="fixed bottom-6 right-6 z-[100]">
    <!-- Floating Button -->
    <button @click="contactOpen = true" class="bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-full p-4 shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex items-center justify-center transition-transform hover:scale-105">
        <ion-icon name="chatbubbles-outline" class="text-2xl"></ion-icon>
    </button>

    <!-- Modal Backdrop -->
    <div x-show="contactOpen" style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[110]"
         @click="contactOpen = false">
         
         <!-- Modal Content -->
         <div @click.stop 
              x-show="contactOpen" style="display: none;"
              x-transition:enter="transition ease-out duration-300 transform"
              x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave="transition ease-in duration-200 transform"
              x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              class="bg-[#1a202c] rounded-2xl shadow-2xl w-full max-w-[340px] overflow-hidden relative mx-4 border border-[#2d3748]">
              
              <!-- Gradient Header -->
              <div class="bg-gradient-to-br from-[#4A72FF] to-[#8C54FF] p-8 text-center relative text-white">
                  <button @click="contactOpen = false" class="absolute top-4 right-4 text-white hover:text-gray-200 transition bg-transparent border-none">
                      <ion-icon name="close-outline" class="text-2xl"></ion-icon>
                  </button>
                  <div class="inline-flex items-center justify-center w-14 h-14 bg-white bg-opacity-20 rounded-full mb-3">
                      <ion-icon name="headset-outline" class="text-3xl"></ion-icon>
                  </div>
                  <h2 class="text-2xl font-bold">Get in Touch</h2>
                  <p class="text-sm mt-1 opacity-90">We're here to help you</p>
              </div>

              <!-- Content Cards -->
              <div class="p-6 space-y-4 bg-[#1a202c]">
                  <!-- WhatsApp -->
                  <a href="https://wa.me/94777182632" target="_blank" class="flex items-center p-4 bg-[#2d3748] hover:bg-[#39455a] rounded-xl transition border border-[#4a5568]">
                      <div class="w-10 h-10 bg-[#1C4532] text-[#38A169] rounded-full flex items-center justify-center text-xl mr-4 flex-shrink-0">
                          <ion-icon name="logo-whatsapp"></ion-icon>
                      </div>
                      <div>
                          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">WHATSAPP</p>
                          <p class="text-white font-bold text-sm">+94 77 718 2632</p>
                      </div>
                  </a>

                  <!-- Call -->
                  <a href="tel:+94777182632" class="flex items-center p-4 bg-[#2d3748] hover:bg-[#39455a] rounded-xl transition border border-[#4a5568]">
                      <div class="w-10 h-10 bg-[#1E3A8A] text-[#60A5FA] rounded-full flex items-center justify-center text-xl mr-4 flex-shrink-0">
                          <ion-icon name="call-outline"></ion-icon>
                      </div>
                      <div>
                          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">CALL US</p>
                          <p class="text-white font-bold text-sm">+94 77 718 2632</p>
                      </div>
                  </a>

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
                          <a href="https://wa.me/94777182632" target="_blank" class="w-8 h-8 bg-[#1a202c] hover:bg-[#38A169] text-gray-300 hover:text-white rounded-full flex items-center justify-center transition">
                              <ion-icon name="logo-whatsapp"></ion-icon>
                          </a>
                          <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" class="w-8 h-8 bg-[#1a202c] hover:bg-[#2563EB] text-gray-300 hover:text-white rounded-full flex items-center justify-center transition">
                              <ion-icon name="logo-facebook"></ion-icon>
                          </a>
                      </div>
                  </div>
              </div>
         </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
HTML;

$content = str_replace('<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>', $contactWidget, $content);
file_put_contents($file, $content);
echo "Added floating contact widget to footer.\n";
?>

