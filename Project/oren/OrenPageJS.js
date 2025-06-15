 function TextWrite() {
            document.getElementById("InfoText").innerText =
                "Info: Name- Oren Bashin, Age - 20, Height - 196 cm, Student at Ruppin Academic Center, 3rd year in computer engineering";
        }


        function toggleContact() {
            let contactElement = document.getElementById("contact");
            if (!contactElement) {
                contactElement = document.createElement("h3");
                contactElement.id = "contact";
                contactElement.innerHTML = "Contact Info: boren1009@gmail.com </br> phone number: 0544685884";
                document.body.appendChild(contactElement);
            } else {
                document.body.removeChild(contactElement);
            }
        }

        TextWrite();