# Moodle Plugin: local_copy

![Moodle Version](https://img.shields.io/badge/Moodle-3.9%20to%204.0-blue.svg)  
![License](https://img.shields.io/badge/License-GPL--v3-brightgreen.svg)  

**local_copy** is a Moodle plugin that provides a practical and efficient functionality to copy activities or resources from one course and paste them into another, making it easier to reuse content across different courses.

## Features

- Copy activities or resources from any Moodle course.
- Paste the copied items into any other Moodle course.
- Compatible with all types of modules, including quizzes, forums, SCORM, and more.
- Simple and intuitive interface to select what to copy and where to paste.

## Requirements

- **Moodle**: Version 3.9 to 4.0.
- **PHP**: Version 7.3 or higher.

## Installation

1. Download the plugin directly from the [GitHub repository](https://github.com/EduardoKrausME/moodle-local_copy).
1. Extract the files and move the `local_copy` folder to the `local` directory of your Moodle installation.
1. Alternatively, upload it via `Site Administration` >> `Plugins` >> `Install Plugins`.
1. Log in to Moodle as an administrator and go to `Site Administration -> General` to complete the installation.

## How to Use

1. Access the source course from which you want to copy activities or resources.
1. Turn on editing in the course.
1. The activity will display a "Copy module to clipboard" link; click it.
![Captura de Tela 2024-09-12 às 13 40 04](https://github.com/user-attachments/assets/ea71f5c7-3617-4aec-8926-998e91cd45d1)
1. Go to the target course.
1. Click the "Paste _XXXXX_ here" button.
![Captura de Tela 2024-09-12 às 13 40 18](https://github.com/user-attachments/assets/0c0856f7-9499-4ea8-9a76-29676d5ead27)
1. The copied activity or resource will be inserted at that location.

## Support

If you encounter issues or bugs, please open an issue in the official GitHub repository: [GitHub Issues](https://github.com/EduardoKrausME/moodle-local_copy/issues).

## Contributions

Contributions are welcome! If you wish to add new features or improve the code, fork the repository and submit a Pull Request.

## License

This plugin is licensed under the [GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.html).
