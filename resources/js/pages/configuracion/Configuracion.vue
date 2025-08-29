<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';


const props = defineProps({

})


</script>


<template>

    <Head title="Stock Detalle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10 mb-5">
                <h1 class="text-[32px] font-bold text-gray-800">Configuración</h1>
            </div>

            <div class="grid grid-cols-4 gap-6">
                <Link :href="route('usuarios')"
                    class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" width="20" height="15" viewBox="0 0 20 15" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M14 13V15H0V13C0 13 0 9.00004 7 9.00004C14 9.00004 14 13 14 13ZM10.5 3.50004C10.5 2.8078 10.2947 2.13111 9.91014 1.55554C9.52556 0.979969 8.97893 0.531365 8.33939 0.266459C7.69985 0.0015519 6.99612 -0.0677598 6.31718 0.0672885C5.63825 0.202337 5.01461 0.53568 4.52513 1.02516C4.03564 1.51465 3.7023 2.13829 3.56725 2.81722C3.4322 3.49615 3.50152 4.19989 3.76642 4.83943C4.03133 5.47897 4.47993 6.0256 5.0555 6.41018C5.63108 6.79477 6.30777 7.00004 7 7.00004C7.92826 7.00004 8.8185 6.63129 9.47487 5.97491C10.1313 5.31853 10.5 4.42829 10.5 3.50004ZM13.94 9.00004C14.5547 9.47578 15.0578 10.0805 15.4137 10.7715C15.7696 11.4626 15.9697 12.2233 16 13V15H20V13C20 13 20 9.37004 13.94 9.00004ZM13 3.67965e-05C12.3118 -0.00316434 11.6388 0.202568 11.07 0.590037C11.6774 1.43877 12.0041 2.45632 12.0041 3.50004C12.0041 4.54375 11.6774 5.5613 11.07 6.41004C11.6388 6.79751 12.3118 7.00324 13 7.00004C13.9283 7.00004 14.8185 6.63129 15.4749 5.97491C16.1313 5.31853 16.5 4.42829 16.5 3.50004C16.5 2.57178 16.1313 1.68154 15.4749 1.02516C14.8185 0.368786 13.9283 3.67965e-05 13 3.67965e-05Z"
                        fill="#0D509C" />
                </svg>
                <span class="text-black text-[16px] font-medium">Usuario</span>
                </Link>
                <Link :href="route('roles')"
                    class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" width="20" height="14" viewBox="0 0 20 14" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M3 6C4.10313 6 5 5.10313 5 4C5 2.89687 4.10313 2 3 2C1.89688 2 1 2.89687 1 4C1 5.10313 1.89688 6 3 6ZM17 6C18.1031 6 19 5.10313 19 4C19 2.89687 18.1031 2 17 2C15.8969 2 15 2.89687 15 4C15 5.10313 15.8969 6 17 6ZM18 7H16C15.45 7 14.9531 7.22188 14.5906 7.58125C15.85 8.27188 16.7437 9.51875 16.9375 11H19C19.5531 11 20 10.5531 20 10V9C20 7.89687 19.1031 7 18 7ZM10 7C11.9344 7 13.5 5.43437 13.5 3.5C13.5 1.56562 11.9344 0 10 0C8.06563 0 6.5 1.56562 6.5 3.5C6.5 5.43437 8.06563 7 10 7ZM12.4 8H12.1406C11.4906 8.3125 10.7688 8.5 10 8.5C9.23125 8.5 8.5125 8.3125 7.85938 8H7.6C5.6125 8 4 9.6125 4 11.6V12.5C4 13.3281 4.67188 14 5.5 14H14.5C15.3281 14 16 13.3281 16 12.5V11.6C16 9.6125 14.3875 8 12.4 8ZM5.40938 7.58125C5.04688 7.22188 4.55 7 4 7H2C0.896875 7 0 7.89687 0 9V10C0 10.5531 0.446875 11 1 11H3.05938C3.25625 9.51875 4.15 8.27188 5.40938 7.58125Z"
                        fill="#0D509C" />
                </svg>
                <span class="text-black text-[16px] font-medium">Roles/Grupos</span>
                </Link>
                <div class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" width="21" height="21" viewBox="0 0 21 21" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.5 4H4.7C6.2 2.7 8 2 10 2C10.3 2 10.6 2 10.9 2.1C11.4 2.2 11.9 1.8 12 1.2C12.1 0.7 11.7 0.2 11.1 0.0999999C10.7 -9.68575e-08 10.4 0 10 0C7.6 0 5.3 0.9 3.5 2.4V1C3.5 0.4 3.1 0 2.5 0C1.9 0 1.5 0.4 1.5 1V5C1.5 5.6 1.9 6 2.5 6H6.5C7.1 6 7.5 5.6 7.5 5C7.5 4.4 7.1 4 6.5 4ZM5 12.5C4.4 12.5 4 12.9 4 13.5V15.3C2.7 13.8 2 12 2 10C2 9.7 2 9.4 2.1 9.1C2.2 8.6 1.8 8.1 1.2 8C0.7 7.9 0.2 8.3 0.0999999 8.9C-9.68575e-08 9.3 0 9.6 0 10C0 12.4 0.9 14.7 2.4 16.5H1C0.4 16.5 0 16.9 0 17.5C0 18.1 0.4 18.5 1 18.5H5C5.3 18.5 5.6 18.3 5.8 18.1C5.8 18 5.9 17.9 5.9 17.8V13.5C6 12.9 5.6 12.5 5 12.5ZM19 3.5C19.6 3.5 20 3.1 20 2.5C20 1.9 19.6 1.5 19 1.5H14.8C14.7 1.5 14.6 1.6 14.5 1.6C14.4 1.7 14.3 1.7 14.3 1.8C14.3 1.9 14.2 2 14.2 2V6.3C14.2 6.9 14.6 7.3 15.2 7.3C15.8 7.3 16.2 6.9 16.2 6.3V4.7C17.5 6.1 18.2 8 18.2 10C18.2 10.3 18.2 10.6 18.1 10.9C18 11.4 18.4 11.9 19 12H19.1C19.6 12 20 11.6 20.1 11.1C20.1 10.7 20.2 10.4 20.2 10C20.2 7.6 19.3 5.3 17.8 3.5H19ZM18.3 14.5L18 14.2C17.9 14.1 17.8 14.1 17.7 14.1H13.5C12.9 14.1 12.5 14.5 12.5 15.1C12.5 15.7 12.9 16.1 13.5 16.1H15.3C13.9 17.4 12 18.1 10 18.1C9.7 18.1 9.4 18.1 9.1 18C8.6 17.9 8.1 18.3 8 18.9C7.9 19.5 8.3 19.9 8.9 20C9.3 20 9.6 20.1 10 20.1C12.4 20.1 14.7 19.2 16.5 17.7V19C16.5 19.6 16.9 20 17.5 20C18.1 20 18.5 19.6 18.5 19V15C18.5 14.8 18.4 14.6 18.3 14.5Z"
                            fill="#0D509C" />
                    </svg>

                    <span class="text-black text-[16px] font-medium">Sectores/Procesos</span>
                </div>
                <Link :href="route('modelos')" class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" width="23" height="15" viewBox="0 0 23 15" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M-1.90735e-06 15L-5.96007e-07 -1.03375e-07L17.5238 1.4286e-06L23 7.50003L17.5238 15L-1.90735e-06 15ZM4.38093 6.42856L12.0476 6.42856L12.0476 4.28574L4.38093 4.28574L4.38093 6.42856ZM16.9762 9.10717C17.8835 9.10717 18.6191 8.38762 18.6191 7.50003C18.6191 6.61243 17.8835 5.89288 16.9762 5.89288C16.0689 5.89288 15.3333 6.61243 15.3333 7.50003C15.3333 8.38762 16.0689 9.10717 16.9762 9.10717ZM4.38093 10.7143L12.0476 10.7143L12.0476 8.57144L4.38093 8.57144L4.38093 10.7143Z"
                            fill="#0D509C" />
                    </svg>
                    <span class="text-black text-[16px] font-medium">Modelos</span>
                </Link>
                <Link :href="route('operarios')" class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" width="16" height="19" viewBox="0 0 16 19" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M8 13C3.58 13 0 14.79 0 17V19H16V17C16 14.79 12.42 13 8 13ZM4 7C4 8.06087 4.42143 9.07828 5.17157 9.82843C5.92172 10.5786 6.93913 11 8 11C9.06087 11 10.0783 10.5786 10.8284 9.82843C11.5786 9.07828 12 8.06087 12 7M7.5 0C7.2 0 7 0.21 7 0.5V3.5H6V1C6 1 3.75 1.86 3.75 4.75C3.75 4.75 3 4.89 3 6H13C12.95 4.89 12.25 4.75 12.25 4.75C12.25 1.86 10 1 10 1V3.5H9V0.5C9 0.21 8.81 0 8.5 0H7.5Z"
                            fill="#0D509C" />
                    </svg>

                    <span class="text-black text-[16px] font-medium">Operarios</span>
                </Link>
                <Link :href="route('stock_minimo')"
                    class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" width="14" height="17" viewBox="0 0 14 17" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <g id="Group 176">
                        <rect id="Rectangle 4716" width="6" height="17" rx="1" fill="#0D509C" />
                        <rect id="Rectangle 4717" x="8" y="9" width="6" height="8" rx="1" fill="#0D509C" />
                    </g>
                </svg>
                <span class="text-black text-[16px] font-medium">Stock Mínimo</span>
                </Link>
                
                <Link :href="route('sector-accesos.index')" class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 1C8.61553 1 7.26216 1.41054 6.11101 2.17971C4.95987 2.94888 4.06266 4.04213 3.53285 5.32122C3.00303 6.6003 2.86441 8.00776 3.13451 9.36563C3.4046 10.7235 4.07129 11.9708 5.05026 12.9497C6.02922 13.9287 7.2765 14.5954 8.63437 14.8655C9.99224 15.1356 11.3997 14.997 12.6788 14.4672C13.9579 13.9373 15.0511 13.0401 15.8203 11.889C16.5895 10.7378 17 9.38447 17 8C17 6.14348 16.2625 4.36301 14.9497 3.05025C13.637 1.7375 11.8565 1 10 1ZM10 13C9.40666 13 8.82664 12.8241 8.33329 12.4944C7.83994 12.1648 7.45542 11.6962 7.22836 11.1481C7.0013 10.5999 6.94189 9.99667 7.05765 9.41473C7.1734 8.83279 7.45912 8.29824 7.87868 7.87868C8.29824 7.45912 8.83279 7.1734 9.41473 7.05765C9.99667 6.94189 10.5999 7.0013 11.1481 7.22836C11.6962 7.45542 12.1648 7.83994 12.4944 8.33329C12.8241 8.82664 13 9.40666 13 10C13 10.7956 12.684 11.5587 12.1213 12.1213C11.5587 12.684 10.7956 13 10 13Z" 
                              fill="#0D509C" stroke="#0D509C" stroke-width="0.5"/>
                        <path d="M14 6L18 10L14 14M6 8H18" stroke="#0D509C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-black text-[16px] font-medium">Códigos de Sectores</span>
                </Link>
                <Link :href="route('problemas')"
                    class="flex items-center bg-white rounded-lg shadow-lg p-5 h-[52px] cursor-pointer">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" width="21" height="19" viewBox="0 0 21 19" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M19.536 18.053L0.999975 18.054C0.82444 18.054 0.651999 18.0077 0.499984 17.92C0.347968 17.8322 0.221734 17.706 0.133968 17.5539C0.0462029 17.4019 -1.11483e-06 17.2295 0 17.0539C1.11487e-06 16.8784 0.0462074 16.706 0.133975 16.554L9.39997 0.499956C9.48775 0.347949 9.61398 0.221722 9.766 0.133962C9.91801 0.0462015 10.0904 0 10.266 0C10.4415 0 10.6139 0.0462015 10.766 0.133962C10.918 0.221722 11.0442 0.347949 11.132 0.499956L20.402 16.553C20.4897 16.705 20.5359 16.8774 20.5359 17.0529C20.536 17.2285 20.4897 17.4009 20.402 17.5529C20.3142 17.705 20.188 17.8312 20.036 17.919C19.884 18.0067 19.7115 18.053 19.536 18.053ZM9.51597 6.54496L9.63198 12.211H10.904L11.021 6.54496H9.51597ZM10.266 15.117C10.746 15.117 11.121 14.748 11.121 14.285C11.121 13.822 10.746 13.459 10.265 13.459C10.1546 13.4563 10.0449 13.4757 9.94213 13.5159C9.83937 13.5562 9.74568 13.6166 9.66653 13.6935C9.58738 13.7704 9.52437 13.8623 9.48117 13.9639C9.43798 14.0654 9.41547 14.1746 9.41498 14.285C9.41498 14.748 9.78997 15.117 10.265 15.117H10.266Z"
                        fill="#0D509C" />
                </svg>

                <div class="text-black text-[16px] font-medium flex flex-col">
                    <span>
                        Problemas/Subproblemas
                    </span>
                    <span>
                        Servicio técnico
                    </span>
                </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>