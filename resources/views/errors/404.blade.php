<x-layouts.base>

<section class="h-screen w-full bg-blue-50">
  <div class="container mx-auto h-full flex flex-col items-center justify-center">
    <div class="sm:w-1/3 bg-white rounded-md shadow-md px-5 py-16 text-center">
      <h1 class="text-6xl font-bold text-red-500 mb-5">400</h1>
      <p class="text-gray-800">
        {{__("Oops! You just found an error page")}}
      </p>
      <p class="my-5 text-gray-500">
        {{__("We are sorry but the page you are looking for was not found")}}
      </p>
      <a href="/" class="bg-green-400 rounded-lg px-4 py-1 flex items-center justify-center">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
          <line x1="5" y1="12" x2="19" y2="12"></line>
          <line x1="5" y1="12" x2="11" y2="18"></line>
          <line x1="5" y1="12" x2="11" y2="6"></line>
        </svg>
        {{__("Take me home")}}
      </a>
    </div>
  </div>
</section>

</x-layouts.base>