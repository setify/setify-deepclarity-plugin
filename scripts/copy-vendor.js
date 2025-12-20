/**
 * Copy vendor files from node_modules to assets folder
 */

const fs = require('fs-extra');
const path = require('path');

const vendorFiles = [
    // Chart.js
    {
        src: 'node_modules/chart.js/dist/chart.umd.js',
        dest: 'assets/js/vendor/chart.min.js'
    },
    // SweetAlert2
    {
        src: 'node_modules/sweetalert2/dist/sweetalert2.min.js',
        dest: 'assets/js/vendor/sweetalert2.min.js'
    },
    {
        src: 'node_modules/sweetalert2/dist/sweetalert2.min.css',
        dest: 'assets/css/vendor/sweetalert2.min.css'
    },
    // Tippy.js
    {
        src: 'node_modules/tippy.js/dist/tippy-bundle.umd.min.js',
        dest: 'assets/js/vendor/tippy-bundle.umd.min.js'
    },
    {
        src: 'node_modules/tippy.js/dist/tippy.css',
        dest: 'assets/css/vendor/tippy.css'
    },
    // Popper.js (required by Tippy)
    {
        src: 'node_modules/@popperjs/core/dist/umd/popper.min.js',
        dest: 'assets/js/vendor/popper.min.js'
    }
];

const rootDir = path.join(__dirname, '..');

console.log('Copying vendor files...');

vendorFiles.forEach(file => {
    const srcPath = path.join(rootDir, file.src);
    const destPath = path.join(rootDir, file.dest);

    try {
        if (fs.existsSync(srcPath)) {
            fs.copySync(srcPath, destPath);
            console.log(`✓ Copied: ${file.dest}`);
        } else {
            console.warn(`✗ Source not found: ${file.src}`);
        }
    } catch (err) {
        console.error(`✗ Error copying ${file.src}:`, err.message);
    }
});

console.log('Done!');
