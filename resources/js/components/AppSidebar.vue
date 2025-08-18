<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const permissions = usePage().props.auth.user.roles[0].permissions.map(permission => permission.name);

</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="bg-white border-r">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <div class="flex items-center justify-center p-4">
                            <img :src="'/logo-teora.png'" alt="Teora" class="w-[172px] h-[60px]" />
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="py-2">
                <nav class="space-y-1">
                    <div class="w-full">
                        <Link v-if="permissions.includes('inicio')" :href="route('dashboard')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="14" height="16" viewBox="0 0 14 16"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path id="Vector" d="M0 15.788V5.288L7 0L14 5.288V15.788H8.308V9.904H5.692V15.788H0Z"
                                    fill="#0D509C" />
                            </svg>
                            <span class="font-medium text-black text-[16px]">Inicio</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('ver ordenes') || permissions.includes('gestionar ordenes')"
                            :href="route('ordenes-fabricacion.index')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="16" height="19" viewBox="0 0 16 19"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g id="Group">
                                    <path id="Vector"
                                        d="M13 1H3C1.89543 1 1 1.89543 1 3V16C1 17.1046 1.89543 18 3 18H13C14.1046 18 15 17.1046 15 16V3C15 1.89543 14.1046 1 13 1Z"
                                        fill="#0D509C" stroke="#0D509C" />
                                    <g id="Vector_2">
                                        <path d="M5 6H11H5ZM5 10H11H5ZM5 14H9H5Z" fill="#0D509C" />
                                        <path d="M5 6H11M5 10H11M5 14H9" stroke="white" stroke-linecap="round" />
                                    </g>
                                </g>
                            </svg>
                            <span class="font-medium text-black text-[16px]">Órdenes de fabricación</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('stock')" :href="route('stock')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="14" height="17" viewBox="0 0 14 17"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g id="Group 176">
                                    <rect id="Rectangle 4716" width="6" height="17" rx="1" fill="#0D509C" />
                                    <rect id="Rectangle 4717" x="8" y="9" width="6" height="8" rx="1" fill="#0D509C" />
                                </g>
                            </svg>
                            <span class="font-medium text-black text-[16px]">Stock</span>
                        </div>
                        </Link>
                        <Link
                            v-if="permissions.includes('ver servicio tecnico') || permissions.includes('gestionar servicio tecnico')"
                            :href="route('servicio_tecnico')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="19" height="19" viewBox="0 0 19 19"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path id="Vector"
                                    d="M16.4 18.025L10.925 12.55L13.025 10.45L18.5 15.925L16.4 18.025ZM2.6 18.025L0.5 15.925L7.4 9.025L5.7 7.325L5 8.025L3.725 6.75V8.8L3.025 9.5L0 6.475L0.7 5.775H2.75L1.5 4.525L5.05 0.975001C5.38333 0.641668 5.74167 0.400001 6.125 0.250001C6.50833 0.100001 6.9 0.0250011 7.3 0.0250011C7.7 0.0250011 8.09167 0.100001 8.475 0.250001C8.85833 0.400001 9.21667 0.641668 9.55 0.975001L7.25 3.275L8.5 4.525L7.8 5.225L9.5 6.925L11.75 4.675C11.6833 4.49167 11.6293 4.3 11.588 4.1C11.5467 3.9 11.5257 3.7 11.525 3.5C11.525 2.51667 11.8627 1.68733 12.538 1.012C13.2133 0.336668 14.0423 -0.00066568 15.025 9.86193e-07C15.275 9.86193e-07 15.5127 0.025001 15.738 0.075001C15.9633 0.125001 16.1923 0.200001 16.425 0.300001L13.95 2.775L15.75 4.575L18.225 2.1C18.3417 2.33333 18.421 2.56233 18.463 2.787C18.505 3.01167 18.5257 3.24933 18.525 3.5C18.525 4.48333 18.1877 5.31267 17.513 5.988C16.8383 6.66333 16.009 7.00067 15.025 7C14.825 7 14.625 6.98333 14.425 6.95C14.225 6.91667 14.0333 6.85833 13.85 6.775L2.6 18.025Z"
                                    fill="#0D509C" />
                            </svg>
                            <span class="font-medium text-black text-[16px]">Servicio técnico</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('ver servicio proceso') || permissions.includes('gestionar servicio proceso')"
                            :href="route('procesos')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="21" height="21" viewBox="0 0 21 21"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g id="Group 257">
                                    <path id="Vector"
                                        d="M6.5 4H4.7C6.2 2.7 8 2 10 2C10.3 2 10.6 2 10.9 2.1C11.4 2.2 11.9 1.8 12 1.2C12.1 0.7 11.7 0.2 11.1 0.0999999C10.7 -9.68575e-08 10.4 0 10 0C7.6 0 5.3 0.9 3.5 2.4V1C3.5 0.4 3.1 0 2.5 0C1.9 0 1.5 0.4 1.5 1V5C1.5 5.6 1.9 6 2.5 6H6.5C7.1 6 7.5 5.6 7.5 5C7.5 4.4 7.1 4 6.5 4ZM5 12.5C4.4 12.5 4 12.9 4 13.5V15.3C2.7 13.8 2 12 2 10C2 9.7 2 9.4 2.1 9.1C2.2 8.6 1.8 8.1 1.2 8C0.7 7.9 0.2 8.3 0.0999999 8.9C-9.68575e-08 9.3 0 9.6 0 10C0 12.4 0.9 14.7 2.4 16.5H1C0.4 16.5 0 16.9 0 17.5C0 18.1 0.4 18.5 1 18.5H5C5.3 18.5 5.6 18.3 5.8 18.1C5.8 18 5.9 17.9 5.9 17.8V13.5C6 12.9 5.6 12.5 5 12.5ZM19 3.5C19.6 3.5 20 3.1 20 2.5C20 1.9 19.6 1.5 19 1.5H14.8C14.7 1.5 14.6 1.6 14.5 1.6C14.4 1.7 14.3 1.7 14.3 1.8C14.3 1.9 14.2 2 14.2 2V6.3C14.2 6.9 14.6 7.3 15.2 7.3C15.8 7.3 16.2 6.9 16.2 6.3V4.7C17.5 6.1 18.2 8 18.2 10C18.2 10.3 18.2 10.6 18.1 10.9C18 11.4 18.4 11.9 19 12H19.1C19.6 12 20 11.6 20.1 11.1C20.1 10.7 20.2 10.4 20.2 10C20.2 7.6 19.3 5.3 17.8 3.5H19ZM18.3 14.5L18 14.2C17.9 14.1 17.8 14.1 17.7 14.1H13.5C12.9 14.1 12.5 14.5 12.5 15.1C12.5 15.7 12.9 16.1 13.5 16.1H15.3C13.9 17.4 12 18.1 10 18.1C9.7 18.1 9.4 18.1 9.1 18C8.6 17.9 8.1 18.3 8 18.9C7.9 19.5 8.3 19.9 8.9 20C9.3 20 9.6 20.1 10 20.1C12.4 20.1 14.7 19.2 16.5 17.7V19C16.5 19.6 16.9 20 17.5 20C18.1 20 18.5 19.6 18.5 19V15C18.5 14.8 18.4 14.6 18.3 14.5Z"
                                        fill="#0D509C" />
                                </g>
                            </svg>
                            <span class="font-medium text-black text-[16px]">Seguimiento por proceso</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('alertas')" :href="route('alertas')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="20" height="18" viewBox="0 0 20 18"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path id="Vector"
                                    d="M10.8227 0.486445L19.8727 16.5405C19.9561 16.6884 20 16.8562 20 17.027C20 17.1978 19.9561 17.3656 19.8727 17.5135C19.7893 17.6614 19.6694 17.7842 19.525 17.8696C19.3806 17.955 19.2168 18 19.05 18H0.950007C0.783243 18 0.619419 17.955 0.475 17.8696C0.33058 17.7842 0.210654 17.6614 0.127274 17.5135C0.0438942 17.3656 -1.05912e-06 17.1978 0 17.027C1.05916e-06 16.8562 0.0438985 16.6884 0.12728 16.5405L9.17727 0.486445C9.26066 0.338545 9.38059 0.21573 9.52501 0.130341C9.66942 0.0449529 9.83324 0 10 0C10.1668 0 10.3306 0.0449529 10.475 0.130341C10.6194 0.21573 10.7393 0.338545 10.8227 0.486445ZM9.04997 13.1351V15.0811H10.95V13.1351H9.04997ZM9.04997 6.3243V11.1892H10.95V6.3243H9.04997Z"
                                    fill="#0D509C" />
                            </svg>

                            <span class="font-medium text-black text-[16px]">Registro de alertas</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('reportes')" :href="route('reportes')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="17" height="17" viewBox="0 0 17 17"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path id="Vector"
                                    d="M7.65 0V17C3.315 16.575 0 12.92 0 8.5C0 4.08 3.315 0.425 7.65 0ZM9.35 0V7.65H17C16.575 3.57 13.43 0.425 9.35 0ZM9.35 9.35V17C13.345 16.575 16.575 13.43 17 9.35H9.35Z"
                                    fill="#0D509C" />
                            </svg>

                            <span class="font-medium text-black text-[16px]">Reportes</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('ver remitos') || permissions.includes('gestionar remitos')"
                            :href="route('remitos')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" width="16" height="19" viewBox="0 0 16 19"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g id="Group">
                                    <path id="Vector"
                                        d="M13 1H3C1.89543 1 1 1.89543 1 3V16C1 17.1046 1.89543 18 3 18H13C14.1046 18 15 17.1046 15 16V3C15 1.89543 14.1046 1 13 1Z"
                                        fill="#0D509C" stroke="#0D509C" />
                                    <g id="Vector_2">
                                        <path d="M5 6H11H5ZM5 10H11H5ZM5 14H9H5Z" fill="#0D509C" />
                                        <path d="M5 6H11M5 10H11M5 14H9" stroke="white" stroke-linecap="round" />
                                    </g>
                                </g>
                            </svg>

                            <span class="font-medium text-black text-[16px]">Remitos</span>
                        </div>
                        </Link>
                        <Link v-if="permissions.includes('historial de despachos')" :href="route('historial')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <div class="flex px-3">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" width="19"
                                height="14" viewBox="0 0 19 14" fill="none">
                                <path
                                    d="M14.6818 12.6875C14.3382 12.6875 14.0087 12.5492 13.7658 12.3031C13.5229 12.0569 13.3864 11.7231 13.3864 11.375C13.3864 11.0269 13.5229 10.6931 13.7658 10.4469C14.0087 10.2008 14.3382 10.0625 14.6818 10.0625C15.0254 10.0625 15.3549 10.2008 15.5978 10.4469C15.8408 10.6931 15.9773 11.0269 15.9773 11.375C15.9773 11.7231 15.8408 12.0569 15.5978 12.3031C15.3549 12.5492 15.0254 12.6875 14.6818 12.6875ZM15.9773 4.8125L17.67 7H13.8182V4.8125M4.31818 12.6875C3.97461 12.6875 3.6451 12.5492 3.40216 12.3031C3.15921 12.0569 3.02273 11.7231 3.02273 11.375C3.02273 11.0269 3.15921 10.6931 3.40216 10.4469C3.6451 10.2008 3.97461 10.0625 4.31818 10.0625C4.66176 10.0625 4.99126 10.2008 5.23421 10.4469C5.47715 10.6931 5.61364 11.0269 5.61364 11.375C5.61364 11.7231 5.47715 12.0569 5.23421 12.3031C4.99126 12.5492 4.66176 12.6875 4.31818 12.6875ZM16.4091 3.5H13.8182V0H1.72727C0.768636 0 0 0.77875 0 1.75V11.375H1.72727C1.72727 12.0712 2.00024 12.7389 2.48613 13.2312C2.97202 13.7234 3.63103 14 4.31818 14C5.00533 14 5.66434 13.7234 6.15023 13.2312C6.63612 12.7389 6.90909 12.0712 6.90909 11.375H12.0909C12.0909 12.0712 12.3639 12.7389 12.8498 13.2312C13.3357 13.7234 13.9947 14 14.6818 14C15.369 14 16.028 13.7234 16.5139 13.2312C16.9998 12.7389 17.2727 12.0712 17.2727 11.375H19V7L16.4091 3.5Z"
                                    fill="#0D509C" />
                            </svg>
                            <span class="font-medium text-black text-[16px]">Historial de despachos</span>
                        </div>
                        </Link>
                    </div>
                </nav>
            </div>

            <div class="mt-8 px-4">
                <h3 class="px-2 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">MAESTROS</h3>
                <nav class="space-y-1">
                    <div class="w-full">
                        <Link v-if="permissions.includes('configuracion')" :href="route('configuracion')"
                            class="flex items-center w-full p-2 hover:bg-blue-50 duration-300 border-b"
                            style="border-color: #F3F3F3;">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" width="19" height="19" viewBox="0 0 19 19" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <g id="Group">
                                <path id="Vector"
                                    d="M11 2.0575C11 1.47333 10.5267 1 9.9425 1H8.725C8.14 1 7.66667 1.47333 7.66667 2.0575C7.66667 2.53917 7.33667 2.9525 6.8875 3.12917C6.81667 3.1575 6.74583 3.1875 6.67667 3.2175C6.23417 3.40917 5.70833 3.35083 5.36667 3.01C5.16837 2.81184 4.8995 2.70053 4.61917 2.70053C4.33883 2.70053 4.06996 2.81184 3.87167 3.01L3.01 3.87167C2.81184 4.06996 2.70053 4.33883 2.70053 4.61917C2.70053 4.8995 2.81184 5.16837 3.01 5.36667C3.35167 5.70833 3.41 6.23333 3.21667 6.67667C3.18629 6.74644 3.15712 6.81672 3.12917 6.8875C2.9525 7.33667 2.53917 7.66667 2.0575 7.66667C1.47333 7.66667 1 8.14 1 8.72417V9.9425C1 10.5267 1.47333 11 2.0575 11C2.53917 11 2.9525 11.33 3.12917 11.7792C3.1575 11.85 3.1875 11.9208 3.21667 11.99C3.40917 12.4325 3.35083 12.9583 3.01 13.3C2.81184 13.4983 2.70053 13.7672 2.70053 14.0475C2.70053 14.3278 2.81184 14.5967 3.01 14.795L3.87167 15.6567C4.06996 15.8548 4.33883 15.9661 4.61917 15.9661C4.8995 15.9661 5.16837 15.8548 5.36667 15.6567C5.70833 15.315 6.23333 15.2567 6.67667 15.4492C6.74583 15.48 6.81667 15.5092 6.8875 15.5375C7.33667 15.7142 7.66667 16.1275 7.66667 16.6092C7.66667 17.1933 8.14 17.6667 8.72417 17.6667H9.9425C10.5267 17.6667 11 17.1933 11 16.6092C11 16.1275 11.33 15.7142 11.7792 15.5367C11.85 15.5092 11.9208 15.48 11.99 15.45C12.4325 15.2567 12.9583 15.3158 13.2992 15.6567C13.3974 15.7549 13.514 15.8328 13.6423 15.886C13.7706 15.9392 13.9082 15.9666 14.0471 15.9666C14.186 15.9666 14.3235 15.9392 14.4519 15.886C14.5802 15.8328 14.6968 15.7549 14.795 15.6567L15.6567 14.795C15.8548 14.5967 15.9661 14.3278 15.9661 14.0475C15.9661 13.7672 15.8548 13.4983 15.6567 13.3C15.315 12.9583 15.2567 12.4333 15.4492 11.99C15.48 11.9208 15.5092 11.85 15.5375 11.7792C15.7142 11.33 16.1275 11 16.6092 11C17.1933 11 17.6667 10.5267 17.6667 9.9425V8.725C17.6667 8.14083 17.1933 7.6675 16.6092 7.6675C16.1275 7.6675 15.7142 7.3375 15.5367 6.88833C15.5087 6.81755 15.4796 6.74726 15.4492 6.6775C15.2575 6.235 15.3158 5.70917 15.6567 5.3675C15.8548 5.1692 15.9661 4.90034 15.9661 4.62C15.9661 4.33966 15.8548 4.0708 15.6567 3.8725L14.795 3.01083C14.5967 2.81268 14.3278 2.70136 14.0475 2.70136C13.7672 2.70136 13.4983 2.81268 13.3 3.01083C12.9583 3.3525 12.4333 3.41083 11.99 3.21833C11.9202 3.18767 11.8499 3.15822 11.7792 3.13C11.33 2.9525 11 2.53833 11 2.0575Z"
                                    fill="#0D509C" stroke="#0D509C" />
                                <path id="Vector_2"
                                    d="M12.6667 9.33333C12.6667 10.2174 12.3155 11.0652 11.6904 11.6904C11.0652 12.3155 10.2174 12.6667 9.33333 12.6667C8.44928 12.6667 7.60143 12.3155 6.97631 11.6904C6.35119 11.0652 6 10.2174 6 9.33333C6 8.44928 6.35119 7.60143 6.97631 6.97631C7.60143 6.35119 8.44928 6 9.33333 6C10.2174 6 11.0652 6.35119 11.6904 6.97631C12.3155 7.60143 12.6667 8.44928 12.6667 9.33333Z"
                                    stroke="white" stroke-width="1.5" />
                            </g>
                        </svg>

                        <span class="font-medium text-black text-[16px]">Configuración</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </SidebarContent>
    </Sidebar>
    <slot />
</template>