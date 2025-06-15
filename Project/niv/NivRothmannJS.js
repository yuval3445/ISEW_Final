 function TextWrite() {
            document.getElementById("InfoText").innerText =
                "Info: Name -niv Rothmann, Age - 25, Height - 183 cm, Student at Ruppin Academic Center, 3rd year in computer engineering";
        }


        function toggleContact() {
            let contactElement = document.getElementById("contact");
            if (!contactElement) {
                contactElement = document.createElement("h3");
                contactElement.id = "contact";
                contactElement.innerHTML = "Contact Info: niv.Rothmann@gmail.com <br> ";
                document.body.appendChild(contactElement);
            } else {
                document.body.removeChild(contactElement);
            }
        }

        TextWrite(); function TextWrite() {
            document.getElementById("InfoText").innerText =
                "Info: Name -niv Rothmann, Age - 25, Height - 183 cm, Student at Ruppin Academic Center, 3rd year in computer engineering";
        }


        function toggleContact() {
            let contactElement = document.getElementById("contact");
            if (!contactElement) {
                contactElement = document.createElement("h3");
                contactElement.id = "contact";
                contactElement.innerHTML = "Contact Info: niv.Rothmann@gmail.com <br> ";
                document.body.appendChild(contactElement);
            } else {
                document.body.removeChild(contactElement);
            }
        }

        TextWrite();
        