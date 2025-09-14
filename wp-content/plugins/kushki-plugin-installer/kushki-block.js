const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { createElement, useEffect } = wp.element;

// Componente que inicializa el formulario Kajita
const KushkiKajitaContent = () => {
  const { public_id, kform_id, test_mode, cart_total } =
    window.kushki_params || {};

  useEffect(() => {
    const scriptUrl = "https://cdn.kushkipagos.com/kushki-checkout.js";
    const scriptId = "kushki-checkout-script";

    if (!document.getElementById(scriptId)) {
      const script = document.createElement("script");
      script.id = scriptId;
      script.src = scriptUrl;
      script.onload = () => {
        if (typeof KushkiCheckout !== "undefined") {
          const options = {
            kformId: kform_id,
            form: "kushki-kajita-form",
            publicMerchantId: public_id,
            inTestEnvironment: test_mode,
            amount: {
              subtotalIva: 0,
              iva: 0,
              subtotalIva0: cart_total,
              currency: "USD",
            },
          };
          new KushkiCheckout(options);
        }
      };
      document.body.appendChild(script);
    }
  }, []);

  return createElement("div", { id: "kushki-kajita-form" });
};

registerPaymentMethod({
  name: "kushki_kajita_debug",
  label: window.kushki_params?.title || "Kushki Kajita",
  content: createElement(KushkiKajitaContent),
  edit: createElement(KushkiKajitaContent),
  canMakePayment: () => true,
  supports: {
    features: ["products"],
  },
});
