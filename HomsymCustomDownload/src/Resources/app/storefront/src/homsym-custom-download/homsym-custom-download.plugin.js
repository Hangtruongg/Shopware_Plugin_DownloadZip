const { PluginBaseClass  } = window;

export default class HomsymCustomDownload extends PluginBaseClass {
    init() {
        console.log("HomsymCustomDownload initialized");
        this._registerEvents();
    }

    _registerEvents() {

        // Select elements
        const downloadZipBtn = document.querySelectorAll(".download-zip-btn"); // 'this.el' is the element the plugin is attached to
        const downloadPopup = document.getElementById('downloadPopup');
        const confirmDownload = document.getElementById('confirmDownload');
        const cancelDownload = document.getElementById('cancelDownload');
        const loadingPopup = document.getElementById("loadingPopup");
        const loadingText = document.getElementById("loadingText");

        if (!downloadZipBtn || !downloadPopup || !confirmDownload || !cancelDownload) {
            console.warn("HomsymCustomDownloadPlugin: Required elements not found!");
            return;
        }

        console.log("Elements found, adding event listeners..."); // Debugging

        // Show the pop-up when the download button is clicked
        downloadZipBtn.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();


                console.log("Download button clicked!"); // Debugging

                // Find the nearest .download-zip-container (to get the correct pop-ups)
                const container = button.closest(".download-zip-container");

                // Get the pop-ups inside this specific container
                const downloadPopup = container.querySelector("#downloadPopup");
                const confirmDownload = container.querySelector("#confirmDownload");
                const cancelDownload = container.querySelector("#cancelDownload")
                const loadingPopup = container.querySelector("#loadingPopup");
                const loadingText = container.querySelector("#loadingText");



                // Get download and order IDs from the button's data attributes
                confirmDownload.dataset.downloadId = button.getAttribute('data-download-id');
                confirmDownload.dataset.orderId = button.getAttribute('data-order-id')

                // Show the pop-up
                downloadPopup.style.display = 'flex';




        // Close the pop-up when clicking the "Cancel" button
        cancelDownload.addEventListener('click', () => {
            console.log("Cancel button clicked! Closing popup."); // Debugging
            downloadPopup.style.display = 'none';
        });

        // Ensure confirmDownload is only assigned an event listener **once**
        if (!confirmDownload.dataset.listenerAttached) {
            confirmDownload.addEventListener("click", async function () {
                const downloadId = confirmDownload.dataset.downloadId;
                const orderId = confirmDownload.dataset.orderId;

                console.log("Confirm button clicked! Showing loading popup...");

                downloadPopup.style.display = "none"; // Hide confirmation popup
                loadingPopup.style.display = "flex";
                loadingText.innerText = "Preparing download...";

                try {
                    const base = window.location.pathname.split('/').slice(0, 3).join('/');
                    const basePath = window.location.pathname.startsWith('/account') ? '' : base;
                    const downloadUrl = `${window.location.origin}${basePath}/account/order/download/zip/${orderId}/${downloadId}`;
                    console.log("Downloading from: ", downloadUrl);

                   /**
                    const basePath = window.location.pathname.split('/').slice(0, 3).join('/');
                    const downloadUrl = `${window.location.origin}${basePath}/account/order/download/zip/${orderId}/${downloadId}`;
                    console.log("Downloading from:", downloadUrl);*/

                    const response = await fetch(downloadUrl, {
                        method: 'GET',
                        credentials: 'include',
                    });

                    if (!response.ok) {
                        throw new Error("Could not fetch resource");
                    }

                    const contentType = response.headers.get("Content-Type");
                    if (!contentType || !contentType.includes("application/zip")) {
                        throw new Error("Invalid file type received. Expected ZIP.");
                    }

                    const contentDisposition = response.headers.get("Content-Disposition");
                    let filename = `order-${orderId}-download.zip`;
                    if (contentDisposition) {
                        const match = contentDisposition.match(/filename="(.+)"/);
                        if (match && match[1]) {
                            filename = match[1];
                        }
                    }

                    const contentLength = response.headers.get("Content-Length");
                    let receivedLength = 0;
                    const reader = response.body.getReader();
                    const chunks = [];

                    while (true) {
                        const {done, value} = await reader.read();
                        if (done) break;
                        chunks.push(value);
                        receivedLength += value.length;
                        loadingText.innerText = contentLength ?
                            `Downloading... ${Math.round((receivedLength / contentLength) * 100)}%` :
                            `Downloading...`;
                    }

                    const blob = new Blob(chunks);
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = blobUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(blobUrl);

                    loadingPopup.style.display = "none";
                    console.log("Download complete!");
                } catch (error) {
                    console.error("Download failed", error);
                    loadingText.innerText = "Download failed!";
                    setTimeout(() => {
                        loadingPopup.style.display = "none";
                    }, 3000);
                }
            });

            confirmDownload.dataset.listenerAttached = "true"; // Prevent duplicate listeners
        }
            });
        });
    }
}